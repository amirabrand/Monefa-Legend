#!/usr/bin/env bash
# =============================================================================
#  Monefa Legend — one-command installer for Ubuntu 20.04 / 22.04 / 24.04
# =============================================================================
#  WHAT IT DOES
#    1. installs Apache + MariaDB + PHP (with required extensions)
#    2. deploys the game files to the web root
#    3. creates the database + user, imports _setup/travian.sql
#    4. writes ConfsData/<domain>/config.php OUTSIDE the web root
#    5. runs install.php (repairs tree, creates admin account, wires gpack)
#    6. creates the Apache vhost + cron jobs (game clock)
#
#  USAGE (as root, from the folder containing this file):
#    sudo bash install_ubuntu.sh --domain=game.example.com \
#         --admin-user=admin --admin-pass='S3cret!' --admin-email=me@x.com
#
#    minimal (uses server IP as domain, generates passwords):
#    sudo bash install_ubuntu.sh
#
#  AFTER INSTALL
#    game        :  http://<domain>/login.php
#    admin panel :  http://<domain>/TdPaneLXP/
# =============================================================================
set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# ---------- pretty output ----------------------------------------------------
B='\033[1m'; G='\033[32m'; Y='\033[33m'; R='\033[31m'; N='\033[0m'
ok()   { printf " ${G}ok${N}   %s\n" "$*"; }
warn() { printf " ${Y}warn${N} %s\n" "$*"; }
die()  { printf " ${R}fail${N} %s\n" "$*" >&2; exit 1; }
step() { printf "\n${B}==> %s${N}\n" "$*"; }

# ---------- defaults ---------------------------------------------------------
DOMAIN=""            # empty => use primary IP
ADMIN_USER="admin"
ADMIN_PASS=""
ADMIN_EMAIL="admin@localhost"
DB_NAME=""
DB_USER=""
DB_PASS=""
WEBROOT="/var/www/monefa-legend"
SECURE="/var/www/monefa-secure"
SRV_NAME="Monefa Legend"
NO_CRON=0

for a in "$@"; do
  case "$a" in
    --domain=*)       DOMAIN="${a#*=}";;
    --admin-user=*)   ADMIN_USER="${a#*=}";;
    --admin-pass=*)   ADMIN_PASS="${a#*=}";;
    --admin-email=*)  ADMIN_EMAIL="${a#*=}";;
    --db-name=*)      DB_NAME="${a#*=}";;
    --db-user=*)      DB_USER="${a#*=}";;
    --db-pass=*)      DB_PASS="${a#*=}";;
    --webroot=*)      WEBROOT="${a#*=}";;
    --secure-root=*)  SECURE="${a#*=}";;
    --server-name=*)  SRV_NAME="${a#*=}";;
    --no-cron)        NO_CRON=1;;
    -h|--help)        sed -n '2,30p' "$0"; exit 0;;
    *) warn "unknown argument: $a";;
  esac
done

# ---------- preflight --------------------------------------------------------
[ "$(id -u)" -eq 0 ] || die "run as root:  sudo bash install_ubuntu.sh ..."
[ -f "$HERE/login.php" ] || die "login.php not found in $HERE - run this from the game folder"
[ -f "$HERE/install.php" ] || die "install.php not found in $HERE"
[ -f "$HERE/_setup/travian.sql" ] || die "_setup/travian.sql missing - package is broken"

if [ -z "$DOMAIN" ]; then
  DOMAIN="$(ip -4 route get 1.1.1.1 2>/dev/null | awk '{print $7; exit}')"
  [ -n "$DOMAIN" ] || DOMAIN="localhost"
  warn "no --domain given, using: $DOMAIN (set DNS later, then edit ConfsData config)"
fi
[ -n "$DB_NAME" ]  || DB_NAME="monefa_legend"
[ -n "$DB_USER" ]  || DB_USER="monefa"
[ -n "$DB_PASS" ]  || DB_PASS="$(head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 20)"
[ -n "$ADMIN_PASS" ] || ADMIN_PASS="$(head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 12)"

echo "=================================================================="
echo "  Monefa Legend installer"
echo "  domain      : $DOMAIN"
echo "  web root    : $WEBROOT"
echo "  secure root : $SECURE"
echo "  database    : $DB_NAME (user: $DB_USER)"
echo "=================================================================="

# ---------- 1. packages ------------------------------------------------------
step "1/7  installing packages (apache / mariadb / php)"
export DEBIAN_FRONTEND=noninteractive
. /etc/os-release
UB="$VERSION_ID"
PHPV=""
case "$UB" in
  20.04) PHPV="8.1"; add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1 || true;;
  22.04) PHPV="8.1";;
  *)     PHPV="8.3";;   # 24.04+
esac
apt-get update -qq
apt-get install -y -qq apache2 mariadb-server "php$PHPV" "libapache2-mod-php$PHPV" \
  "php$PHPV-mysql" "php$PHPV-mbstring" "php$PHPV-gd" "php$PHPV-curl" "php$PHPV-zip" \
  "php$PHPV-xml" "php$PHPV-intl" unzip rsync >/dev/null
ok "packages installed (php$PHPV)"

# ---------- 2. database ------------------------------------------------------
step "2/7  creating database + user"
mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;"
ok "database ready: $DB_NAME"

# ---------- 3. deploy files --------------------------------------------------
step "3/7  deploying game files"
mkdir -p "$WEBROOT" "$SECURE"
rsync -a --delete \
  --exclude 'install_ubuntu.sh' --exclude '.git*' --exclude 'README.md' \
  "$HERE"/ "$WEBROOT"/
ok "deployed to $WEBROOT"

# ---------- 4. ConfsData (outside web root) ---------------------------------
step "4/7  writing ConfsData/<domain>/config.php"
mkdir -p "$SECURE/ConfsData/$DOMAIN"
cp "$HERE/ConfsData/config.php"    "$SECURE/ConfsData/$DOMAIN/config.php"
cp "$HERE/ConfsData/botConfig.php" "$SECURE/ConfsData/botConfig.php"
sed -i "s#{{DB_USER}}#$DB_USER#g; s#{{DB_PASS}}#$DB_PASS#g; s#{{DB_NAME}}#$DB_NAME#g; s#{{DOMAIN}}#$DOMAIN#g" \
  "$SECURE/ConfsData/$DOMAIN/config.php" "$SECURE/ConfsData/botConfig.php"
chmod -R 750 "$SECURE"; chown -R root:www-data "$SECURE"
ok "config written to $SECURE/ConfsData/$DOMAIN/config.php"

# ---------- 5. run the kit installer ----------------------------------------
step "5/7  running install.php (repair + import + admin account)"
PHP_BIN="$(command -v "php$PHPV" || command -v php)"
"$PHP_BIN" "$WEBROOT/install.php" \
  --site-root="$WEBROOT" \
  --conf-root="$SECURE/ConfsData" \
  --db-host=localhost --db-user="$DB_USER" --db-pass="$DB_PASS" --db-name="$DB_NAME" \
  --host="$DOMAIN" \
  --admin-user="$ADMIN_USER" --admin-pass="$ADMIN_PASS" --admin-email="$ADMIN_EMAIL" \
  || {
    warn "install.php reported problems - trying --fix-only + manual import"
    "$PHP_BIN" "$WEBROOT/install.php" --fix-only --site-root="$WEBROOT" || true
    mysql "$DB_NAME" < "$WEBROOT/_setup/travian.sql"
    warn "base data imported manually; open http://$DOMAIN/install.php to finish"
  }
ok "installer finished"

# ---------- 6. apache vhost --------------------------------------------------
step "6/7  configuring apache"
a2enmod rewrite >/dev/null 2>&1 || true
cat > /etc/apache2/sites-available/monefa-legend.conf <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    DocumentRoot $WEBROOT
    <Directory $WEBROOT>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/monefa-error.log
    CustomLog \${APACHE_LOG_DIR}/monefa-access.log combined
</VirtualHost>
EOF
a2ensite monefa-legend >/dev/null 2>&1 || true
a2dissite 000-default >/dev/null 2>&1 || true
chown -R www-data:www-data "$WEBROOT"
find "$WEBROOT" -type d -exec chmod 755 {} \;
find "$WEBROOT" -type f -exec chmod 644 {} \;
apache2ctl configtest >/dev/null 2>&1 && systemctl reload apache2 || { systemctl restart apache2; }
ok "apache vhost live (http://$DOMAIN)"

# ---------- 7. cron (game clock) --------------------------------------------
if [ "$NO_CRON" -eq 0 ]; then
  step "7/7  creating cron jobs"
  cat > /etc/cron.d/monefa-legend <<EOF
# Monefa Legend — game clock (queue processing)
* * * * * www-data $PHP_BIN $WEBROOT/dorf1.php  >/dev/null 2>&1
* * * * * www-data $PHP_BIN $WEBROOT/application/queue.php >/dev/null 2>&1
EOF
  chmod 644 /etc/cron.d/monefa-legend
  ok "cron installed (/etc/cron.d/monefa-legend)"
else
  warn "cron skipped (--no-cron) - the game clock will NOT advance without it!"
fi

# php.ini sane values
INIF="$(ls /etc/php/$PHPV/apache2/php.ini 2>/dev/null || true)"
if [ -n "$INIF" ]; then
  sed -i "s/^memory_limit.*/memory_limit = 512M/; s/^upload_max_filesize.*/upload_max_filesize = 32M/; s/^post_max_size.*/post_max_size = 32M/; s/^max_execution_time.*/max_execution_time = 120/" "$INIF"
  systemctl reload apache2
fi

# firewall (only if ufw active)
if command -v ufw >/dev/null 2>&1 && ufw status 2>/dev/null | grep -q "Status: active"; then
  ufw allow 80/tcp >/dev/null; ufw allow 443/tcp >/dev/null
  ok "ufw: opened 80/443"
fi

cat <<EOF

==================================================================
  ${G}Monefa Legend is installed!${N}
==================================================================
  game         :  http://$DOMAIN/login.php
  admin panel  :  http://$DOMAIN/TdPaneLXP/
  admin user   :  $ADMIN_USER
  admin pass   :  $ADMIN_PASS
  db name      :  $DB_NAME
  db user      :  $DB_USER
  db pass      :  $DB_PASS
  config file  :  $SECURE/ConfsData/$DOMAIN/config.php
------------------------------------------------------------------
  next steps:
   * point DNS of $DOMAIN to this server, then add HTTPS:
       apt install certbot python3-certbot-apache && certbot --apache
   * delete $WEBROOT/install.php (only if you used the manual fallback)
   * keep cron running - it drives the game clock
==================================================================
EOF
