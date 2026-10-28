#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Travian Legend - one-command installer for Linux / VPS / cPanel shell
#
#   1. put the package somewhere, e.g.  /var/www/travian/
#        /var/www/travian/game/           <- domains/tt1  (login.php lives here)
#        /var/www/travian/ConfsData/      <- ConfsData
#        /var/www/travian/sql/            <- the .sql dump
#   2. copy install.php (one file, self-contained) into /var/www/travian/game/
#   3. ./install.sh --db-user=root --db-pass=secret --db-name=travian \
#                   --host=game.example.com --admin-pass=secret
#
# Everything after "--" is handed to install.php.
# ---------------------------------------------------------------------------
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN="${PHP_BIN:-php}"

ok()   { printf '\033[32m  ok\033[0m   %s\n' "$*"; }
warn() { printf '\033[33m warn\033[0m  %s\n' "$*"; }
die()  { printf '\033[31m fail\033[0m  %s\n' "$*" >&2; exit 1; }

echo "== Travian Legend installer =="
echo

# --- php ---------------------------------------------------------------
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  die "php not found. install php-cli (php8.1-cli / php8.2-cli) or set PHP_BIN=/path/to/php"
fi
ok "php: $($PHP_BIN -v | head -n1)"

$PHP_BIN -r 'exit(version_compare(PHP_VERSION, "7.4.0", ">=") ? 0 : 1);' \
  || die "PHP >= 7.4 required"

for ext in pdo_mysql mysqli mbstring json; do
  if $PHP_BIN -r "exit(extension_loaded('$ext') ? 0 : 1);"; then
    ok "ext $ext"
  else
    die "missing php extension: $ext  (apt install php-$ext / yum install php-$ext)"
  fi
done

for ext in gd curl zip; do
  if $PHP_BIN -r "exit(extension_loaded('$ext') ? 0 : 1);"; then
    ok "ext $ext"
  else
    warn "extension $ext missing - hero avatars / mail / uploads will degrade"
  fi
done

# --- layout ------------------------------------------------------------
ROOT="$HERE"
if [ ! -f "$ROOT/login.php" ] && [ -f "$HERE/game/login.php" ]; then
  ROOT="$HERE/game"
fi
if [ -f "$ROOT/login.php" ]; then
  ok "site root: $ROOT"
else
  warn "login.php not found next to install.sh - pass --site-root=/path/to/game"
fi

CONF="$(dirname "$ROOT")/ConfsData"
if [ -d "$CONF" ]; then
  ok "ConfsData: $CONF"
else
  warn "ConfsData not found in $(dirname "$ROOT") - copy it there (it must NOT be inside the web root)"
fi

# --- writable ----------------------------------------------------------
for d in "$ROOT" "$ROOT/application"; do
  if [ -d "$d" ]; then
    if [ -w "$d" ]; then
      ok "writable $d"
    else
      die "not writable: $d  (chown -R www-data:www-data /var/www/travian)"
    fi
  fi
done
if [ -d "$CONF" ] && [ ! -w "$CONF" ]; then
  die "ConfsData not writable: $CONF"
fi

# --- run ---------------------------------------------------------------
INSTALLER="$HERE/install.php"
[ -f "$INSTALLER" ] || INSTALLER="$ROOT/install.php"
[ -f "$INSTALLER" ] || die "install.php not found (put it next to install.sh or in the site root)"

echo
if ! $PHP_BIN "$INSTALLER" --site-root="$ROOT" "$@"; then
  die "installer failed - see the log above"
fi

cat <<'EOF'

--------------------------------------------------------------------
 next steps
--------------------------------------------------------------------
  * delete install.php from the web root
  * the installer already added application/legacy_compat.php (mysql_* shim),
    application/lang_fallback.php (102 missing UI constants) and
    application/env.php (paths) - keep all three
  * php.ini: upload_max_filesize=32M  post_max_size=32M  max_execution_time=60
  * add a cron job (queue.php) if you want the game clock to move:
        * * * * * php /path/to/game/queue.php >/dev/null 2>&1
  * Apache: allow .htaccess (AllowOverride All). nginx users: see
    README-INSTALL.md -> "nginx" for the deny rules.
EOF
