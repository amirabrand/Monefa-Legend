<?php
/**
 * Travian Legend - single-file installer / repair tool
 * ==========================================================================
 * THIS IS THE ONLY FILE YOU NEED. Copy it next to login.php and open it.
 *
 *   WEB :  https://your-domain/install.php
 *          https://your-domain/install.php?mode=fix          (repair only)
 *          https://your-domain/install.php?mode=check        (verify an install)
 *
 *   CLI :  php install.php --db-user=root --db-pass=x --db-name=travian \
 *                          --host=game.example.com --admin-pass=secret
 *          php install.php --fix-only [--dry-run]
 *          php install.php --check --db-user=.. --db-name=..
 *          php install.php --help
 *
 * What it does:
 *   1. repairs the 12 files that ship with PHP parse errors (licence-header
 *      damage) and quarantines the dead bank.php
 *   2. rewrites the /home/<user>/... hard-codes -> portable application/config.php
 *      + application/env.php, adds the mysql_* shim (legacy_compat.php) and
 *      empty defaults for the 102 undefined UI constants (lang_fallback.php)
 *   3. removes the auth backdoor (Multihunter), the CLI session forge,
 *      payment/SMTP/telegram secrets and the debug leftovers
 *   4. creates the database, imports the .sql dump (utf8mb3 -> utf8mb4,
 *      statement-by-statement, strict mode off), sets the config row,
 *      creates/repairs the admin account, writes ConfsData/<domain>/config.php
 *      and copies the gpack graphics so the game does not need the old CDN
 *
 * Every touched file keeps a "<file>.kit-bak" backup.
 * --------------------------------------------------------------------------
 * @package travian-legend-kit
 */

/**
 * Travian Legend - installer / fixer core
 * -------------------------------------------------------------
 * Works on the "travian legend" package extracted from the DirectAdmin backup
 * (domains/tt1 = game engine, ConfsData = per-domain config, sql/ = dumps).
 *
 * PHP 7.4 - 8.3 (8.4 works, but the engine itself is old).
 *
 * @package travian-legend-kit
 */

class TravianInstaller
{
    /** @var string site root (folder that contains login.php) */
    public $root = '';

    /** @var string folder that holds ConfsData (parent of the site root by default) */
    public $confRoot = '';

    /** @var array<string> human readable log lines */
    public $log = array();

    /** @var array<string> errors */
    public $errors = array();

    /** @var array<int,array> preflight rows: label, ok, required, note */
    public $checks = array();

    /** @var bool */
    public $dryRun = false;

    /** @var int id of the admin account that was updated/created */
    public $adminId = 0;

    /** @var array settings */
    public $opt = array(
        'dbHost'      => 'localhost',
        'dbPort'      => 3306,
        'dbUser'      => '',
        'dbPass'      => '',
        'dbName'      => 'travian',
        'dbPrefix'    => '',
        'sqlFile'     => '',
        'host'        => '',          // SERVER_NAME / domain of the game
        'serverName'  => 'Travian Legend',
        'homepage'    => '',
        'speed'       => 3,
        'lang'        => 'fa',
        'gold'        => 100000,
        'opening'     => 0,           // 0 = now
        'regStatus'   => 1,
        'adminUser'   => 'admin',
        'adminPass'   => '',
        'adminEmail'  => '',
        'gpack'       => 'auto',      // auto | skip | /path/to/public_html
        'gpackUrl'    => '',          // public URL of the asset root
        'protect'     => true,
        'force'       => false,
    );

    public function __construct($root = null, $confRoot = null)
    {
        if ($root === null) {
            $root = $this->detectRoot();
        }
        $this->root = $this->norm($root);
        $this->confRoot = $confRoot !== null ? $this->norm($confRoot) : $this->detectConfRoot();
    }

    /**
     * ConfsData normally lives one level ABOVE the web root (the original
     * DirectAdmin layout was /home/<user>/ConfsData). In the raw package it is
     * two levels up: <pkg>/domains/tt1 -> <pkg>/ConfsData.
     */
    public function detectConfRoot()
    {
        $candidates = array(
            dirname($this->root) . '/ConfsData',
            dirname(dirname($this->root)) . '/ConfsData',
            $this->root . '/ConfsData',
        );
        foreach ($candidates as $c) {
            if (is_dir($c) && (is_dir($c . '/tt1.traviana.ir') || is_file($c . '/botConfig.php') || is_dir($c . '/tt2.traviana.ir'))) {
                return $this->norm($c);
            }
        }
        foreach ($candidates as $c) {
            if (is_dir($c)) {
                return $this->norm($c);
            }
        }
        return $this->norm(dirname($this->root) . '/ConfsData');
    }

    /* ---------------------------------------------------------------------
     * helpers
     * ------------------------------------------------------------------ */

    public function norm($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        $real = @realpath($path);
        if ($real !== false) {
            $path = str_replace('\\', '/', $real);
        }
        return rtrim($path, '/');
    }

    public function p($rel)
    {
        return $this->root . '/' . ltrim(str_replace('\\', '/', $rel), '/');
    }

    public function say($msg)
    {
        $this->log[] = $msg;
        if (PHP_SAPI === 'cli') {
            echo $msg . PHP_EOL;
        }
        return $msg;
    }

    public function err($msg)
    {
        $this->errors[] = $msg;
        $this->say('  [ERROR] ' . $msg);
        return $msg;
    }

    public function detectRoot()
    {
        $candidates = array();
        $here = $this->norm(__DIR__);
        $candidates[] = $here;
        $candidates[] = $this->norm($here . '/..');
        $candidates[] = $this->norm($here . '/../..');
        foreach ($candidates as $c) {
            if (is_file($c . '/login.php') && is_file($c . '/application/config.php')) {
                return $c;
            }
        }
        // nothing found: assume we are dropped inside the game folder
        return $here;
    }

    public function isInstalled()
    {
        return is_file($this->p('install.lock')) || is_file($this->p('application/env.php'));
    }

    /** copy file to "<file>.kit-bak" once */
    public function backup($rel)
    {
        $file = $this->p($rel);
        if (!is_file($file)) {
            return false;
        }
        $bak = $file . '.kit-bak';
        if (is_file($bak)) {
            return true;
        }
        if ($this->dryRun) {
            return true;
        }
        return @copy($file, $bak);
    }

    /**
     * Replace literal strings inside a file. Returns true when something changed.
     */
    public function replace($rel, array $pairs, $required = false)
    {
        $file = $this->p($rel);
        if (!is_file($file)) {
            if ($required) {
                $this->err('missing file: ' . $rel);
            }
            return false;
        }
        $src = file_get_contents($file);
        if ($src === false) {
            $this->err('cannot read: ' . $rel);
            return false;
        }
        $new = $src;
        $hits = 0;
        foreach ($pairs as $from => $to) {
            if ($from === '') {
                continue;
            }
            $count = 0;
            $new = str_replace($from, $to, $new, $count);
            $hits += $count;
        }
        if ($new === $src) {
            return false;
        }
        $this->backup($rel);
        if (!$this->dryRun) {
            if (file_put_contents($file, $new) === false) {
                $this->err('cannot write: ' . $rel);
                return false;
            }
        }
        $this->say('  patched ' . $rel . ' (' . $hits . ' replacement' . ($hits === 1 ? '' : 's') . ')');
        return true;
    }

    /** regex replace inside a file; $pattern must include delimiters */
    public function replaceRegex($rel, $pattern, $replacement, $limit = -1)
    {
        $file = $this->p($rel);
        if (!is_file($file)) {
            return false;
        }
        $src = file_get_contents($file);
        if ($src === false) {
            return false;
        }
        $new = preg_replace($pattern, $replacement, $src, $limit);
        if ($new === null || $new === $src) {
            return false;
        }
        $this->backup($rel);
        if (!$this->dryRun) {
            if (file_put_contents($file, $new) === false) {
                $this->err('cannot write: ' . $rel);
                return false;
            }
        }
        $this->say('  patched ' . $rel);
        return true;
    }

    public function write($rel, $content, $requiredDir = true)
    {
        $file = $this->p($rel);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (!$this->dryRun && !@mkdir($dir, 0775, true)) {
                $this->err('cannot create folder: ' . $dir);
                return false;
            }
        }
        if (is_file($file) && file_get_contents($file) === $content) {
            return true;
        }
        if ($this->dryRun) {
            $this->say('  [dry-run] would write ' . $rel);
            return true;
        }
        if (@file_put_contents($file, $content) === false) {
            $this->err('cannot write: ' . $rel);
            return false;
        }
        $this->say('  wrote ' . $rel);
        return true;
    }

    public function writeAbs($file, $content)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (!$this->dryRun && !@mkdir($dir, 0775, true)) {
                $this->err('cannot create folder: ' . $dir);
                return false;
            }
        }
        if (is_file($file) && file_get_contents($file) === $content) {
            return true;
        }
        if ($this->dryRun) {
            $this->say('  [dry-run] would write ' . $file);
            return true;
        }
        if (@file_put_contents($file, $content) === false) {
            $this->err('cannot write: ' . $file);
            return false;
        }
        $this->say('  wrote ' . $file);
        return true;
    }

    public function phpFiles($dir, array $skip = array())
    {
        $out = array();
        if (!is_dir($dir)) {
            return $out;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $f) {
            /** @var SplFileInfo $f */
            if (!$f->isFile()) {
                continue;
            }
            $name = str_replace('\\', '/', $f->getPathname());
            $lower = strtolower($name);
            if (substr($lower, -4) !== '.php') {
                continue;
            }
            $rel = str_replace(str_replace('\\', '/', $dir) . '/', '', $name);
            $skipHit = false;
            foreach ($skip as $s) {
                if (strpos('/' . $rel, '/' . trim($s, '/') . '/') !== false || strpos($rel, $s) === 0) {
                    $skipHit = true;
                    break;
                }
            }
            if (!$skipHit) {
                $out[] = $rel;
            }
        }
        sort($out);
        return $out;
    }

    /* ---------------------------------------------------------------------
     * 1. preflight
     * ------------------------------------------------------------------ */

    public function preflight()
    {
        $this->checks = array();

        $phpOk = version_compare(PHP_VERSION, '7.4.0', '>=');
        $this->checks[] = array(
            'PHP >= 7.4', $phpOk, true,
            'current: ' . PHP_VERSION,
        );

        foreach (array('pdo_mysql' => true, 'mysqli' => true, 'mbstring' => true, 'json' => true, 'gd' => false, 'curl' => false, 'zip' => false, 'fileinfo' => false) as $ext => $required) {
            $this->checks[] = array(
                'extension ' . $ext,
                extension_loaded($ext),
                $required,
                $required ? 'required' : 'optional (hero avatar / mail / upload)',
            );
        }

        $this->checks[] = array(
            'site root found', is_file($this->p('login.php')) && is_file($this->p('application/config.php')), true,
            $this->root,
        );
        $this->checks[] = array(
            'site root writable', is_writable($this->root), true, $this->root,
        );
        $this->checks[] = array(
            'ConfsData writable / creatable',
            is_dir($this->confRoot) ? is_writable($this->confRoot) : is_writable(dirname($this->confRoot)),
            true,
            $this->confRoot,
        );
        $this->checks[] = array(
            'application/ writable', is_writable($this->p('application')), true, $this->p('application'),
        );
        $this->checks[] = array(
            'SQL dump available',
            $this->findSqlFile() !== '',
            true,
            $this->findSqlFile() !== '' ? $this->findSqlFile() : 'put the .sql dump next to install.php',
        );
        $this->checks[] = array(
            'installer not locked',
            !$this->isInstalled() || $this->opt['force'],
            true,
            $this->isInstalled() ? 'already installed -> use --force to re-run' : 'ok',
        );

        $fatal = false;
        foreach ($this->checks as $c) {
            if ($c[2] && !$c[1]) {
                $fatal = true;
            }
        }
        return !$fatal;
    }

    public function findSqlFile()
    {
        if (!empty($this->opt['sqlFile']) && is_file($this->opt['sqlFile'])) {
            return $this->norm($this->opt['sqlFile']);
        }
        $names = array(
            'travian.sql', 'game.sql', 'tt1.sql', 'database.sql',
            'cp52249_tt1 new version.sql', 'cp52249_tt1.sql',
        );
        $dirs = array(
            $this->root . '/_setup',
            $this->root . '/_setup/sql',
            $this->root . '/sql',
            $this->root,
            dirname($this->root) . '/_setup',
            dirname($this->root),
            dirname($this->root) . '/sql',
            dirname($this->root) . '/sql game and serverloby+goldbank',
            __DIR__,
            dirname(__DIR__) . '/sql',
        );
        foreach ($dirs as $d) {
            foreach ($names as $n) {
                if (is_file($d . '/' . $n)) {
                    return $this->norm($d . '/' . $n);
                }
            }
        }
        // fall back: first .sql found in the obvious folders
        foreach ($dirs as $d) {
            if (!is_dir($d)) {
                continue;
            }
            $list = glob($d . '/*.sql');
            if ($list) {
                usort($list, function ($a, $b) {
                    return filesize($b) - filesize($a);
                });
                return $this->norm($list[0]);
            }
        }
        return '';
    }

    /* ---------------------------------------------------------------------
     * 2. code fixes
     * ------------------------------------------------------------------ */

    /**
     * The package ships with a licence doc-block glued on top of ~47 files.
     * In 5 of them the block was inserted *without* closing PHP mode, so the
     * whole rest of the file is parsed as PHP -> "syntax error, unexpected '<'".
     * In 4 others a second "<?php" was inserted inside PHP mode -> parse error.
     */
    public function fixBrokenHeaders()
    {
        $changed = 0;
        $files = $this->phpFiles($this->root, array(
            'application/library', 'application/vendor', 'vendor', 'gpack', 'gpackcdn',
            'cgi-bin', 'img', 'fonts', 'Templates', 'hcache', 'mcache', 'reorder_demo',
        ));
        foreach ($files as $rel) {
            $file = $this->p($rel);
            $src = @file_get_contents($file);
            if ($src === false || strpos($src, '@package') === false) {
                continue;
            }
            // the doc-block must be the very first thing in the file
            if (!preg_match('~^(\xEF\xBB\xBF)?\s*<\?php\s*\r?\n\s*/\*\*~', $src)) {
                continue;
            }
            $end = strpos($src, '*/');
            if ($end === false) {
                continue;
            }
            $head = substr($src, 0, $end + 2);
            $tail = substr($src, $end + 2);
            $new = $src;
            $eol = (strpos($tail, "\r\n") !== false) ? "\r\n" : "\n";

            if (preg_match('~^\s*<\?php[ \t]*(\r?\n)?~', $tail, $m)) {
                // duplicate open tag while already in PHP mode
                $tail2 = preg_replace('~^\s*<\?php[ \t]*(\r?\n)?~', '', $tail, 1);
                $new = $head . $eol . $tail2;
                $why = 'removed duplicate <?php';
            } elseif (preg_match('~^\s*(<|$)~', $tail)) {
                // markup (or nothing) right after the doc-block: leave PHP mode
                $new = $head . $eol . '?>' . $tail;
                $why = 'added missing ?> after the licence block';
            } else {
                continue;
            }
            if ($new === $src) {
                continue;
            }
            $this->backup($rel);
            if (!$this->dryRun) {
                @file_put_contents($file, $new);
            }
            $this->say('  fixed header: ' . $rel . ' -> ' . $why);
            $changed++;
        }
        if ($changed === 0) {
            $this->say('  license-header damage: none found (already fixed)');
        }
        return $changed;
    }

    /** source of application/legacy_compat.php - embedded so this kit is one file */
    public function legacyCompatSource()
    {
        return <<<'TVCOMPAT'
<?php
/**
 * Legacy compatibility layer for the Travian engine.
 * Copied to application/legacy_compat.php by travian-legend-kit.
 *
 * Why this file exists:
 *   - 125 call sites in the game still use mysql_query() / mysql_fetch_assoc() /
 *     mysql_error() ... which were REMOVED in PHP 7 -> "Call to undefined
 *     function mysql_query()" (fatal) on every modern PHP.
 *   - 129 call sites build table names as TB_PREFIX."users" but TB_PREFIX is
 *     never defined -> "Undefined constant" (fatal in PHP 8, broken SQL in 7).
 *
 * The functions below re-implement only what the code calls, on top of mysqli.
 */

if (!defined('TB_PREFIX')) {
    define('TB_PREFIX', '');
}

if (!function_exists('mysql_query')) {

    /** @return mysqli|false */
    function __tv_mysql_link()
    {
        if (isset($GLOBALS['__tv_mysql_link']) && $GLOBALS['__tv_mysql_link'] instanceof mysqli) {
            return $GLOBALS['__tv_mysql_link'];
        }
        if (isset($GLOBALS['__tv_mysql_link']) && $GLOBALS['__tv_mysql_link'] === false) {
            return false;
        }
        if (!class_exists('mysqli')) {
            trigger_error('legacy mysql_* compatibility: the mysqli extension is not loaded', E_USER_WARNING);
            $GLOBALS['__tv_mysql_link'] = false;
            return false;
        }
        $host = defined('SQL_SERVER') ? SQL_SERVER : 'localhost';
        $user = defined('SQL_USER') ? SQL_USER : '';
        $pass = defined('SQL_PASS') ? SQL_PASS : '';
        $db   = defined('SQL_DB') ? SQL_DB : '';
        $link = @new mysqli($host, $user, $pass, $db);
        if ($link->connect_errno) {
            trigger_error('legacy mysql_* compatibility: ' . $link->connect_error, E_USER_WARNING);
            $GLOBALS['__tv_mysql_link'] = false;
            return false;
        }
        @$link->set_charset('utf8mb4');
        $GLOBALS['__tv_mysql_link'] = $link;
        return $link;
    }

    function mysql_query($query, $link = null)
    {
        if (!($link instanceof mysqli)) {
            $link = __tv_mysql_link();
        }
        if (!$link) {
            return false;
        }
        $res = @$link->query($query);
        return $res === false ? false : $res;
    }

    function mysql_fetch_assoc($result)
    {
        return $result instanceof mysqli_result ? $result->fetch_assoc() : false;
    }

    function mysql_fetch_array($result, $type = MYSQLI_BOTH)
    {
        return $result instanceof mysqli_result ? $result->fetch_array($type) : false;
    }

    function mysql_fetch_row($result)
    {
        return $result instanceof mysqli_result ? $result->fetch_row() : false;
    }

    function mysql_fetch_object($result, $class = 'stdClass')
    {
        return $result instanceof mysqli_result ? $result->fetch_object($class) : false;
    }

    function mysql_fetch_all($result)
    {
        $rows = array();
        if (!($result instanceof mysqli_result)) {
            return $rows;
        }
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    function mysql_num_rows($result)
    {
        return $result instanceof mysqli_result ? $result->num_rows : 0;
    }

    function mysql_num_fields($result)
    {
        return $result instanceof mysqli_result ? $result->field_count : 0;
    }

    function mysql_result($result, $row, $field = 0)
    {
        if (!($result instanceof mysqli_result)) {
            return false;
        }
        if (!$result->data_seek((int) $row)) {
            return false;
        }
        $values = $result->fetch_row();
        if (!is_array($values)) {
            return false;
        }
        return isset($values[$field]) ? $values[$field] : null;
    }

    function mysql_error($link = null)
    {
        if (!($link instanceof mysqli)) {
            $link = isset($GLOBALS['__tv_mysql_link']) ? $GLOBALS['__tv_mysql_link'] : null;
        }
        return $link instanceof mysqli ? $link->error : 'no mysql connection';
    }

    function mysql_errno($link = null)
    {
        if (!($link instanceof mysqli)) {
            $link = isset($GLOBALS['__tv_mysql_link']) ? $GLOBALS['__tv_mysql_link'] : null;
        }
        return $link instanceof mysqli ? $link->errno : 0;
    }

    function mysql_affected_rows($link = null)
    {
        if (!($link instanceof mysqli)) {
            $link = __tv_mysql_link();
        }
        return $link ? $link->affected_rows : -1;
    }

    function mysql_insert_id($link = null)
    {
        if (!($link instanceof mysqli)) {
            $link = __tv_mysql_link();
        }
        return $link ? $link->insert_id : 0;
    }

    function mysql_real_escape_string($value, $link = null)
    {
        if (!($link instanceof mysqli)) {
            $link = __tv_mysql_link();
        }
        return $link ? $link->real_escape_string((string) $value) : addslashes((string) $value);
    }

    function mysql_escape_string($value)
    {
        return mysql_real_escape_string($value);
    }

    function mysql_select_db($db, $link = null)
    {
        if (!($link instanceof mysqli)) {
            $link = __tv_mysql_link();
        }
        return $link ? @$link->select_db($db) : false;
    }

    function mysql_set_charset($charset, $link = null)
    {
        if (!($link instanceof mysqli)) {
            $link = __tv_mysql_link();
        }
        return $link ? @$link->set_charset($charset) : false;
    }

    function mysql_free_result($result)
    {
        if ($result instanceof mysqli_result) {
            $result->free();
        }
        return true;
    }

    function mysql_close($link = null)
    {
        if (!($link instanceof mysqli)) {
            $link = isset($GLOBALS['__tv_mysql_link']) ? $GLOBALS['__tv_mysql_link'] : null;
        }
        if ($link instanceof mysqli) {
            $link->close();
        }
        $GLOBALS['__tv_mysql_link'] = null;
        return true;
    }

    function mysql_ping($link = null)
    {
        if (!($link instanceof mysqli)) {
            $link = __tv_mysql_link();
        }
        return $link ? @$link->ping() : false;
    }

    function mysql_get_server_info($link = null)
    {
        if (!($link instanceof mysqli)) {
            $link = __tv_mysql_link();
        }
        return $link ? $link->server_info : '';
    }
}
TVCOMPAT;
    }

    /** write application/legacy_compat.php (mysql_* shim + TB_PREFIX) */
    public function writeLegacyCompat()
    {
        $src = $this->legacyCompatSource();
        $dst = $this->p('application/legacy_compat.php');
        if (is_file($dst) && file_get_contents($dst) === $src) {
            return true;
        }
        if ($this->dryRun) {
            $this->say('  [dry-run] would write application/legacy_compat.php');
            return true;
        }
        if (!@file_put_contents($dst, $src)) {
            $this->err('cannot write application/legacy_compat.php');
            return false;
        }
        $this->say('  wrote application/legacy_compat.php (mysql_* shim + TB_PREFIX)');
        return true;
    }

    /** source of application/lang_fallback.php - embedded, see BUGS-REPORT.md §۱۵ */
    public function langFallbackSource()
    {
        return <<<'TVLANG'
<?php
/**
 * Fallback definitions for UI constants that the views use but that no
 * language file (application/Lang/{fa,en,ar}/Lang.php) defines.
 *
 * Without this file PHP 8 aborts the page with:
 *     Error: Undefined constant "NOTICE1"
 * (PHP 7 only raised a notice, which is why nobody noticed: the code was
 * written for PHP 5/7.)
 *
 * Values are empty strings, so a missing label renders blank instead of
 * killing the page. When you add the real text to a Lang.php and that
 * language is active, you may delete the matching line here
 * (both use if (!defined()), so nothing breaks either way).
 *
 * Generated by travian-legend-kit - count: 102
 */

/* building names used by the war/defence simulator */
if (!defined('B25')) { define('B25', 'Palace'); }
if (!defined('B26')) { define('B26', 'Treasure Chamber'); }
if (!defined('B31')) { define('B31', 'Great Warehouse'); }
if (!defined('B32')) { define('B32', 'Great Granary'); }
if (!defined('B33')) { define('B33', 'Wonder of the World'); }
if (!defined('B34')) { define('B34', 'Stone Mason\'s Lodge'); }

/* UI labels missing from every language file */
if (!defined('ALREADY_INVITED')) { define('ALREADY_INVITED', ''); }
if (!defined('ALREADY_IN_ALLY')) { define('ALREADY_IN_ALLY', ''); }
if (!defined('AT_CANCEL')) { define('AT_CANCEL', ''); }
if (!defined('AT_REINFORCEMENT')) { define('AT_REINFORCEMENT', ''); }
if (!defined('AT_TROOPS')) { define('AT_TROOPS', ''); }
if (!defined('BL_CAPITAL')) { define('BL_CAPITAL', ''); }
if (!defined('BL_CLOSE')) { define('BL_CLOSE', ''); }
if (!defined('BL_TRAVIANANS')) { define('BL_TRAVIANANS', ''); }
if (!defined('COSTCP')) { define('COSTCP', ''); }
if (!defined('COSTRES')) { define('COSTRES', ''); }
if (!defined('DBSERVIDORES')) { define('DBSERVIDORES', ''); }
if (!defined('FIRSTPAGE')) { define('FIRSTPAGE', ''); }
if (!defined('GP_ENABLE')) { define('GP_ENABLE', ''); }
if (!defined('HEROATTRSPEED')) { define('HEROATTRSPEED', ''); }
if (!defined('HERO_HERODEADORNOTHERE')) { define('HERO_HERODEADORNOTHERE', ''); }
if (!defined('HOWCP')) { define('HOWCP', ''); }
if (!defined('HS_HERODEAD')) { define('HS_HERODEAD', ''); }
if (!defined('ITEMATTRSPEED')) { define('ITEMATTRSPEED', ''); }
if (!defined('JR_ATTACK_SCOUT')) { define('JR_ATTACK_SCOUT', ''); }
if (!defined('KARG')) { define('KARG', ''); }
if (!defined('LASTPAGE')) { define('LASTPAGE', ''); }
if (!defined('LOGIN_NO_JAVASCRIPT')) { define('LOGIN_NO_JAVASCRIPT', ''); }
if (!defined('MINPROTECTION')) { define('MINPROTECTION', ''); }
if (!defined('MK_PLAYERS')) { define('MK_PLAYERS', ''); }
if (!defined('MP_ALLYMARKMYALL')) { define('MP_ALLYMARKMYALL', ''); }
if (!defined('MP_FLAGS')) { define('MP_FLAGS', ''); }
if (!defined('MP_FLDYMARKMYALL')) { define('MP_FLDYMARKMYALL', ''); }
if (!defined('MP_MARKALLY')) { define('MP_MARKALLY', ''); }
if (!defined('MP_MARKFIELD')) { define('MP_MARKFIELD', ''); }
if (!defined('MP_MARKPLAYER')) { define('MP_MARKPLAYER', ''); }
if (!defined('MP_OWNALLYMARK')) { define('MP_OWNALLYMARK', ''); }
if (!defined('MP_OWNFLDMARK')) { define('MP_OWNFLDMARK', ''); }
if (!defined('MP_OWNPLYMARK')) { define('MP_OWNPLYMARK', ''); }
if (!defined('MP_PLYMARKMYALL')) { define('MP_PLYMARKMYALL', ''); }
if (!defined('MP_SENDMERCHANT')) { define('MP_SENDMERCHANT', ''); }
if (!defined('MP_SENDTROOP')) { define('MP_SENDTROOP', ''); }
if (!defined('MS_ARCHIVE')) { define('MS_ARCHIVE', ''); }
if (!defined('MS_SENT')) { define('MS_SENT', ''); }
if (!defined('MV_ATTACK')) { define('MV_ATTACK', ''); }
if (!defined('NAME_EMPTY')) { define('NAME_EMPTY', ''); }
if (!defined('NAME_NO_EXIST')) { define('NAME_NO_EXIST', ''); }
if (!defined('NEWSTR')) { define('NEWSTR', ''); }
if (!defined('NEXTPAGE')) { define('NEXTPAGE', ''); }
if (!defined('NOTICE1')) { define('NOTICE1', ''); }
if (!defined('NOTICE10')) { define('NOTICE10', ''); }
if (!defined('NOTICE11')) { define('NOTICE11', ''); }
if (!defined('NOTICE12')) { define('NOTICE12', ''); }
if (!defined('NOTICE13')) { define('NOTICE13', ''); }
if (!defined('NOTICE14')) { define('NOTICE14', ''); }
if (!defined('NOTICE15')) { define('NOTICE15', ''); }
if (!defined('NOTICE16')) { define('NOTICE16', ''); }
if (!defined('NOTICE17')) { define('NOTICE17', ''); }
if (!defined('NOTICE18')) { define('NOTICE18', ''); }
if (!defined('NOTICE19')) { define('NOTICE19', ''); }
if (!defined('NOTICE2')) { define('NOTICE2', ''); }
if (!defined('NOTICE20')) { define('NOTICE20', ''); }
if (!defined('NOTICE21')) { define('NOTICE21', ''); }
if (!defined('NOTICE22')) { define('NOTICE22', ''); }
if (!defined('NOTICE23')) { define('NOTICE23', ''); }
if (!defined('NOTICE3')) { define('NOTICE3', ''); }
if (!defined('NOTICE4')) { define('NOTICE4', ''); }
if (!defined('NOTICE5')) { define('NOTICE5', ''); }
if (!defined('NOTICE6')) { define('NOTICE6', ''); }
if (!defined('NOTICE7')) { define('NOTICE7', ''); }
if (!defined('NOTICE8')) { define('NOTICE8', ''); }
if (!defined('NOTICE9')) { define('NOTICE9', ''); }
if (!defined('PD_CLAY')) { define('PD_CLAY', ''); }
if (!defined('PD_CROP')) { define('PD_CROP', ''); }
if (!defined('PD_IRON')) { define('PD_IRON', ''); }
if (!defined('PD_LUMBER')) { define('PD_LUMBER', ''); }
if (!defined('PF_INHABITANTS')) { define('PF_INHABITANTS', ''); }
if (!defined('PREVPAGE')) { define('PREVPAGE', ''); }
if (!defined('RANK')) { define('RANK', ''); }
if (!defined('REPORT_BUILDINGDAMAGED')) { define('REPORT_BUILDINGDAMAGED', ''); }
if (!defined('REPORT_DEFEND')) { define('REPORT_DEFEND', ''); }
if (!defined('REPORT_NOREPORT')) { define('REPORT_NOREPORT', ''); }
if (!defined('REPORT_PARTICIPANT')) { define('REPORT_PARTICIPANT', ''); }
if (!defined('REPORT_REINFS')) { define('REPORT_REINFS', ''); }
if (!defined('REPORT_WALLDAMAGEDED')) { define('REPORT_WALLDAMAGEDED', ''); }
if (!defined('RES_CROPBALANCE')) { define('RES_CROPBALANCE', ''); }
if (!defined('RES_CROPBALANCETITLE')) { define('RES_CROPBALANCETITLE', ''); }
if (!defined('RES_FULLIN')) { define('RES_FULLIN', ''); }
if (!defined('RES_MOREINFO')) { define('RES_MOREINFO', ''); }
if (!defined('RES_PRODUCTION')) { define('RES_PRODUCTION', ''); }
if (!defined('SAME_NAME')) { define('SAME_NAME', ''); }
if (!defined('SELECTALL')) { define('SELECTALL', ''); }
if (!defined('SELL_CP')) { define('SELL_CP', ''); }
if (!defined('SELL_RES')) { define('SELL_RES', ''); }
if (!defined('SI_SAVE')) { define('SI_SAVE', ''); }
if (!defined('STARTNOW')) { define('STARTNOW', ''); }
if (!defined('TB_PREFIX')) { define('TB_PREFIX', ''); }
if (!defined('WARSIM_CTAR1')) { define('WARSIM_CTAR1', ''); }
if (!defined('WARSIM_CTAR1LVL')) { define('WARSIM_CTAR1LVL', ''); }
if (!defined('WARSIM_ETC')) { define('WARSIM_ETC', ''); }
if (!defined('WEEK')) { define('WEEK', ''); }
if (!defined('XTRAVIAN')) { define('XTRAVIAN', ''); }
TVLANG;
    }

    /** write application/lang_fallback.php (empty defaults for missing UI labels) */
    public function writeLangFallback()
    {
        $src = $this->langFallbackSource();
        $dst = $this->p('application/lang_fallback.php');
        if (is_file($dst) && file_get_contents($dst) === $src) {
            return true;
        }
        if ($this->dryRun) {
            $this->say('  [dry-run] would write application/lang_fallback.php');
            return true;
        }
        if (!@file_put_contents($dst, $src)) {
            $this->err('cannot write application/lang_fallback.php');
            return false;
        }
        $this->say('  wrote application/lang_fallback.php (' . substr_count($src, 'define(') . ' fallback constants)');
        return true;
    }

    /** includes that point at files which do not exist on any server */
    public function fixBrokenIncludes()
    {
        $n = 0;

        // payment.php: "/../application/config.php" is an absolute path (filesystem
        // root); the next line already includes the same file correctly.
        if (is_file($this->p('payment.php'))) {
            $n += (int) $this->replace('payment.php', array(
                'include ("/../application/config.php");' =>
                    '// include ("/../application/config.php"); // removed by installer: broken absolute path',
            ));
        }

        // ajax.php: two includes of "/home/tramian/connectionbank.php" (another
        // user's home dir). Build the handle from the server config instead.
        if (is_file($this->p('ajax.php'))) {
            $n += (int) $this->replace('ajax.php', array(
                'include("/home/tramian/connectionbank.php");' =>
                    '$bakermysqli = @new mysqli(SQL_SERVER, SQL_USER, SQL_PASS, SQL_DB);',
            ));
        }

        // application/TdPaneLXP/views/*: "../application/BBCode.php" resolves to
        // application/application/BBCode.php -> include it by directory instead
        foreach (array(
            'application/TdPaneLXP/views/allmsgs.php',
            'application/TdPaneLXP/views/msg.php',
        ) as $rel) {
            if (is_file($this->p($rel))) {
                $n += (int) $this->replace($rel, array(
                    'include("../application/BBCode.php");' =>
                        'include(__DIR__ . "/../../BBCode.php");',
                ));
            }
        }

        if ($n === 0) {
            $this->say('  broken includes: none found (already fixed)');
        }
        return $n;
    }

    /** portable CONFSDATA / GP_IMGREAL instead of "/home/<user>/..." */
    public function fixApplicationConfig()
    {
        $rel = 'application/config.php';
        $content = <<<'PHP'
<?php
/**
 * Portable bootstrap configuration (rewritten by travian-legend-kit).
 * The original version hard-coded  /home/<user>/ConfsData/...  and used
 * $_SERVER['SERVER_NAME'] without validation (Host-header path traversal).
 */
header('Content-Type: text/html; charset=UTF-8');

if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', str_replace('\\', '/', realpath(__DIR__)));
}
if (!defined('APP_MAIN_PATH')) {
    define('APP_MAIN_PATH', dirname(APP_BASE_PATH));
}

$__env = APP_BASE_PATH . '/env.php';
if (is_file($__env)) {
    require_once $__env;
}

/* mysql_* shim + TB_PREFIX for the legacy call sites (PHP 7+ removed them) */
$__compat = APP_BASE_PATH . '/legacy_compat.php';
if (is_file($__compat)) {
    require_once $__compat;
}

/* empty defaults for the UI constants no language file defines */
$__langfb = APP_BASE_PATH . '/lang_fallback.php';
if (is_file($__langfb)) {
    require_once $__langfb;
}

if (!defined('TRAVIAN_ROOT')) {
    define('TRAVIAN_ROOT', APP_MAIN_PATH);
}
if (!defined('TRAVIAN_CONFROOT')) {
    define('TRAVIAN_CONFROOT', dirname(APP_MAIN_PATH) . '/ConfsData');
}
if (!defined('TRAVIAN_DEFAULT_HOST')) {
    define('TRAVIAN_DEFAULT_HOST', '');
}

/* ---- which host asks for a config? (validated, CLI friendly) ---------- */
$__host = '';
if (PHP_SAPI !== 'cli') {
    if (!empty($_SERVER['HTTP_HOST'])) {
        $__host = $_SERVER['HTTP_HOST'];
    } elseif (!empty($_SERVER['SERVER_NAME'])) {
        $__host = $_SERVER['SERVER_NAME'];
    }
}
if ($__host === '' && !empty($_SERVER['SERVER_NAME'])) {
    $__host = $_SERVER['SERVER_NAME'];
}
if ($__host === '') {
    $__host = TRAVIAN_DEFAULT_HOST;
}
$__host = strtolower(trim($__host));
$__host = preg_replace('~:\d+$~', '', $__host);
$__host = preg_replace('~^www\.~i', '', $__host);
if (!preg_match('~^[a-z0-9]([a-z0-9.-]{0,190})[a-z0-9]$~', $__host)) {
    $__host = '';
}

$__conf = TRAVIAN_CONFROOT . '/' . $__host . '/config.php';
if ($__host === '' || !is_file($__conf)) {
    if (TRAVIAN_DEFAULT_HOST !== '') {
        $__candidate = TRAVIAN_CONFROOT . '/' . TRAVIAN_DEFAULT_HOST . '/config.php';
        if (is_file($__candidate)) {
            $__conf = $__candidate;
        }
    }
}
if (!is_file($__conf)) {
    http_response_code(500);
    exit('Travian: server configuration not found (' . htmlspecialchars($__conf) . '). Run install.php first.');
}
define('CONFSDATA', str_replace('\\', '/', dirname($__conf)) . '/');

if (!defined('GP_IMG_PATH')) {
    $__gp = TRAVIAN_ROOT . '/img/';
    if (!is_dir($__gp)) {
        $__gp = dirname(TRAVIAN_ROOT) . '/gpack/img/';
    }
    define('GP_IMG_PATH', $__gp);
}
define('GP_IMGREAL', rtrim(str_replace('\\', '/', GP_IMG_PATH), '/') . '/');

include(CONFSDATA . 'config.php');
PHP;
        $file = $this->p($rel);
        if (is_file($file)) {
            $old = file_get_contents($file);
            if ($old === $content) {
                return false;
            }
            $this->backup($rel);
        }
        return $this->write($rel, $content);
    }

    /** PDO wrapper: transactions before connect, wrong SET NAMES, log path, importSQLDump */
    public function fixDbClass()
    {
        $rel = 'application/DB.php';
        if (!is_file($this->p($rel))) {
            return false;
        }
        $this->replace($rel, array(
            "SET NAMES utf8'" => "SET NAMES utf8mb4'",
            '$this->pdo->beginTransaction();' =>
                'if (!$this->pdo) { $this->Connect(); } $this->pdo->beginTransaction();',
            '$this->pdo->commit();' =>
                'if (!$this->pdo) { $this->Connect(); } $this->pdo->commit();',
            '$this->pdo->rollBack();' =>
                'if (!$this->pdo) { $this->Connect(); } $this->pdo->rollBack();',
            'file_put_contents(\'application/queue2/error_log.txt\', var_export($data, true) . "\r\n\r\n", FILE_APPEND);' =>
                '$__dir = defined(\'APP_MAIN_PATH\') ? APP_MAIN_PATH . \'/logs\' : sys_get_temp_dir();' . "\n" .
                '            if (!is_dir($__dir)) { @mkdir($__dir, 0775, true); }' . "\n" .
                '            $__f = $__dir . \'/db-errors.log\';' . "\n" .
                '            if (is_dir($__dir) && (!is_file($__f) || filesize($__f) < 5242880)) {' . "\n" .
                '                @file_put_contents($__f, var_export($data, true) . "\r\n\r\n", FILE_APPEND | LOCK_EX);' . "\n" .
                '            }',
        ));

        // importSQLDump()/importSQLDumper() executed the *path* instead of the file content
        $this->replaceRegex(
            $rel,
            '~public function importSQLDump\(\$sqlDumpFile\)\s*\{.*?\n    \}~s',
            'public function importSQLDump($sqlDumpFile)' . "\n" .
            '    {' . "\n" .
            '        if (!$this->pdo) { $this->Connect(); }' . "\n" .
            '        $sql = is_file($sqlDumpFile) ? file_get_contents($sqlDumpFile) : $sqlDumpFile;' . "\n" .
            '        try {' . "\n" .
            '            $this->pdo->exec($sql);' . "\n" .
            '            return true;' . "\n" .
            '        } catch (PDOException $e) {' . "\n" .
            '            return false;' . "\n" .
            '        }' . "\n" .
            '    }'
        );

        // add a quote helper used by the hardened login()
        $needle = '    public function get_last_id()';
        $helper = '    /** safe string quoting for the few legacy queries that are not prepared */' . "\n" .
                  '    public function esc($value)' . "\n" .
                  '    {' . "\n" .
                  '        if (!$this->pdo) { $this->Connect(); }' . "\n" .
                  '        return $this->pdo->quote((string) $value);' . "\n" .
                  '    }' . "\n\n" .
                  '    public function get_last_id()';
        $this->replace($rel, array($needle => $helper));

        return true;
    }

    /** replace Database::login() - it contained a "Multihunter" master-password backdoor */
    public function fixLoginBackdoor()
    {
        $rel = 'application/Database/db_MYSQL.php';
        $file = $this->p($rel);
        if (!is_file($file)) {
            $this->err('missing ' . $rel);
            return false;
        }
        $src = file_get_contents($file);
        $pos = strpos($src, 'function login($username, $password)');
        if ($pos === false) {
            $this->err('login() not found in ' . $rel);
            return false;
        }
        // brace matching from the first "{" after the signature
        $start = strpos($src, '{', $pos);
        if ($start === false) {
            return false;
        }
        $depth = 0;
        $len = strlen($src);
        $end = null;
        $inS = null;
        for ($i = $start; $i < $len; $i++) {
            $ch = $src[$i];
            if ($inS !== null) {
                if ($ch === '\\') {
                    $i++;
                } elseif ($ch === $inS) {
                    $inS = null;
                }
                continue;
            }
            if ($ch === "'" || $ch === '"') {
                $inS = $ch;
                continue;
            }
            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }
        if ($end === null) {
            $this->err('cannot locate end of login()');
            return false;
        }
        $replacement = 'function login($username, $password)' . "\n" .
            "\t" . '{' . "\n" .
            "\t\t" . '// FIXED: the original code accepted the "Multihunter" password for EVERY account.' . "\n" .
            "\t\t" . '$u = $this->esc($username);' . "\n" .
            "\t\t" . '$q = $this->queryFetch(\'SELECT password, username FROM users WHERE `username` = \' . $u . \' OR email = \' . $u);' . "\n" .
            "\t\t" . 'if (empty($q[\'username\']) || empty($q[\'password\'])) {' . "\n" .
            "\t\t\t" . 'return false;' . "\n" .
            "\t\t" . '}' . "\n" .
            "\t\t" . '$expected = md5($password . mb_convert_case($q[\'username\'], MB_CASE_LOWER, \'UTF-8\'));' . "\n" .
            "\t\t" . 'return hash_equals((string) $q[\'password\'], (string) $expected);' . "\n" .
            "\t" . '}';
        // keep the indentation of the class body (one tab)
        $new = substr($src, 0, $pos) . $replacement . substr($src, $end + 1);
        $this->backup($rel);
        if (!$this->dryRun) {
            file_put_contents($file, $new);
        }
        $this->say('  fixed auth backdoor in ' . $rel);
        return true;
    }

    /** Account.php fakes an admin session when REMOTE_ADDR is missing (CLI/cron) */
    public function fixCliSessionForge()
    {
        $rel = 'application/Account.php';
        if (!is_file($this->p($rel))) {
            return false;
        }
        return $this->replace($rel, array(
            'if(!isset($_SERVER[\'REMOTE_ADDR\']) && !$session->logged_in){' =>
                'if(false && !isset($_SERVER[\'REMOTE_ADDR\']) && !$session->logged_in){ // DISABLED by installer: CLI session forge',
        ));
    }

    /** two files that are broken beyond the licence-header damage */
    public function fixOrphans()
    {
        $n = 0;

        // Admin/Infobox_message.php: "if ($msg) {" is never closed -> unexpected EOF.
        // The file is not included anywhere in this build, but a stray parse error
        // in a shared folder is a trap.
        $rel = 'application/Admin/Infobox_message.php';
        if (is_file($this->p($rel))) {
            $src = file_get_contents($this->p($rel));
            if (strpos($src, "\n}\n\$mysqli->close();") === false && strpos($src, '$mysqli->close();') !== false) {
                if ($this->replace($rel, array('$mysqli->close();' => "}\n\$mysqli->close();"))) {
                    $n++;
                }
            }
        }

        // views/Plus/bank.php: includes banksystem.php + configfile4816...php which
        // do NOT exist in the package, and has a stray "}" -> parse error.
        // Nothing includes it, so it is quarantined instead of guessed at.
        $rel = 'application/views/Plus/bank.php';
        if (is_file($this->p($rel))) {
            $dst = $this->p($rel . '.disabled');
            if (!$this->dryRun) {
                if (!is_file($dst) && @rename($this->p($rel), $dst)) {
                    $this->say('  quarantined dead file ' . $rel . ' -> ' . $rel . '.disabled');
                    $n++;
                }
            } else {
                $this->say('  [dry-run] would quarantine ' . $rel);
                $n++;
            }
        }
        return $n;
    }

    /** demo/debug leftovers that must not stay on a public server */
    public function removeDebugFiles()
    {
        $targets = array(
            'php.php', 'update.php', 'constant.php', 'error_log',
            'application/queue2/error_log.txt',
            'application/queue2/log.txt',
            'application/queue2/error_speeds.txt',
            'application/queue2/_log.txt',
            'application/queue.txt',
        );
        $removed = 0;
        foreach ($targets as $rel) {
            $file = $this->p($rel);
            if (!is_file($file)) {
                continue;
            }
            if ($this->dryRun) {
                $this->say('  [dry-run] would delete ' . $rel);
                $removed++;
                continue;
            }
            // keep the original bytes for the first 256 KB, then drop
            $size = filesize($file);
            if ($size > 262144) {
                @unlink($file);
            } else {
                @rename($file, $file . '.removed');
            }
            $this->say('  removed leftover ' . $rel . ' (' . $size . ' bytes)');
            $removed++;
        }
        return $removed;
    }

    /** deny web access to code/config folders */
    public function writeHtaccess()
    {
        if ($this->opt['protect'] !== true) {
            return false;
        }
        $deny = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
              . "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";

        // the shipped application/.htaccess points to /home/admin/... -> 500 / endless auth prompt
        $this->write('application/.htaccess', "Options -Indexes\n" . $deny);

        if (is_dir($this->confRoot)) {
            $this->writeAbs($this->confRoot . '/.htaccess', "Options -Indexes\n" . $deny);
        }
        $this->writeAbs($this->p('logs/.htaccess'), "Options -Indexes\n" . $deny);
        $this->writeAbs($this->p('sql/.htaccess'), "Options -Indexes\n" . $deny);
        // the package's staging folder (dump + config template)
        $this->writeAbs($this->p('_setup/.htaccess'), "Options -Indexes\n" . $deny);
        return true;
    }

    /** hard-coded e-mail / telegram / paypal secrets -> blank them */
    public function scrubSecrets()
    {
        $rel = 'ConfsData/botConfig.php';
        // handled by writeBotConfig()
        $serverConf = $this->confRoot . '/' . $this->opt['host'] . '/config.php';
        if (is_file($serverConf)) {
            $src = file_get_contents($serverConf);
            $new = $src;
            $new = preg_replace("~'paypalAPIUser'\s*=>\s*'[^']*'~", "'paypalAPIUser' => ''", $new);
            $new = preg_replace("~'paypalAPIPwd'\s*=>\s*'[^']*'~", "'paypalAPIPwd' => ''", $new);
            $new = preg_replace("~'paypalAPISign'\s*=>\s*'[^']*'~", "'paypalAPISign' => ''", $new);
            $new = preg_replace("~'ZarinpalapiKey'\s*=>\s*'[^']*'~", "'ZarinpalapiKey' => ''", $new);
            $new = preg_replace("~'SMTPPass'\s*=>\s*'[^']*'~", "'SMTPPass' => ''", $new);
            if ($new !== $src) {
                @copy($serverConf, $serverConf . '.kit-bak');
                if (!$this->dryRun) {
                    @file_put_contents($serverConf, $new);
                }
                $this->say('  blanked payment / SMTP keys in ConfsData config');
            }
        }

        // telegram bot token inside the auto-reinstaller
        $rel2 = 'application/Models/Installertd.php';
        if (is_file($this->p($rel2))) {
            $this->replaceRegex($rel2, '~\$botToken\s*=\s*"[0-9]+:[A-Za-z0-9_\-]+"~', '$botToken = ""');
            $this->replaceRegex($rel2, "~\\\$channelId\\s*=\\s*'[^']*'~", "\$channelId = ''");
        }
        return true;
    }

    /** auto-wipe on a timer is dangerous, and ss.sql path / include were broken */
    public function fixAutoReinstaller()
    {
        $rel = 'application/Models/Installertd.php';
        if (!is_file($this->p($rel))) {
            return false;
        }
        $this->replace($rel, array(
            "include_once('jdf.php');" => "include_once(APP_MAIN_PATH . '/jdf.php');",
            '$sqlFilePath = $adjustedPath . \'/ss.sql\';' =>
                '$sqlFilePath = dirname(CONFSDATA) . \'/ss.sql\';',
            'if ($database->query($sql) === TRUE) {' =>
                'if ($database->query($sql) !== null) {',
            'if (time() > $qData[\'reinstalltime\'] ) {' =>
                'if (defined(\'AUTO_REINSTALL\') && AUTO_REINSTALL && time() > $qData[\'reinstalltime\']) {',
        ));
        return true;
    }

    /* ---------------------------------------------------------------------
     * 3. database
     * ------------------------------------------------------------------ */

    /** @return PDO */
    public function connect($withDb = true)
    {
        $dsn = 'mysql:host=' . $this->opt['dbHost']
            . ';port=' . (int) $this->opt['dbPort']
            . ($withDb ? ';dbname=' . $this->opt['dbName'] : '')
            . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $this->opt['dbUser'], $this->opt['dbPass'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ));
        $pdo->exec("SET SESSION sql_mode = ''");
        $pdo->exec("SET NAMES utf8mb4");
        return $pdo;
    }

    public function ensureDatabase(PDO $pdo)
    {
        $name = $this->opt['dbName'];
        if (!preg_match('~^[A-Za-z0-9_]+$~', $name)) {
            throw new RuntimeException('invalid database name: ' . $name);
        }
        try {
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        } catch (PDOException $e) {
            // shared hosting often has no CREATE DATABASE privilege
            $this->say('  (CREATE DATABASE not permitted - assuming "' . $name . '" already exists)');
        }
        $pdo->exec('USE `' . $name . '`');
        return true;
    }

    /** split a mysqldump file into single statements (quote / comment aware) */
    public function splitSql($sql)
    {
        $out = array();
        $len = strlen($sql);
        $buf = '';
        $inS = null;
        $inLine = false;
        $inBlock = false;
        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if ($inLine) {
                if ($ch === "\n") {
                    $inLine = false;
                    $buf .= $ch;
                }
                continue;
            }
            if ($inBlock) {
                if ($ch === '*' && $next === '/') {
                    $inBlock = false;
                    $i++;
                }
                continue;
            }
            if ($inS !== null) {
                $buf .= $ch;
                if ($ch === '\\' && $next !== '') {
                    $buf .= $next;
                    $i++;
                    continue;
                }
                if ($ch === $inS) {
                    $inS = null;
                }
                continue;
            }
            if ($ch === "'" || $ch === '"' || $ch === '`') {
                $inS = $ch;
                $buf .= $ch;
                continue;
            }
            if ($ch === '-' && $next === '-' && ($i + 2 >= $len || preg_match('~\s~', $sql[$i + 2]))) {
                $inLine = true;
                $i++;
                continue;
            }
            if ($ch === '#') {
                $inLine = true;
                continue;
            }
            if ($ch === '/' && $next === '*') {
                $inBlock = true;
                $i++;
                continue;
            }
            if ($ch === ';') {
                $stmt = trim($buf);
                if ($stmt !== '') {
                    $out[] = $stmt;
                }
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        $stmt = trim($buf);
        if ($stmt !== '') {
            $out[] = $stmt;
        }
        return $out;
    }

    /**
     * Import the dump. MySQL-8 / MariaDB safe: normalises utf8mb3, disables
     * strict mode for the session, keeps going after single-statement errors
     * and reports them.
     */
    public function importSql($file, $progressEvery = 200)
    {
        $file = $this->norm($file);
        if (!is_file($file)) {
            $this->err('SQL file not found: ' . $file);
            return false;
        }
        $size = filesize($file);
        $this->say('  importing ' . basename($file) . ' (' . round($size / 1048576, 2) . ' MB)');

        $sql = file_get_contents($file);
        if ($sql === false) {
            $this->err('cannot read ' . $file);
            return false;
        }

        // portability normalisation
        $sql = str_replace(array("\r\n"), array("\n"), $sql);
        $sql = str_replace('utf8mb3_general_ci', 'utf8mb4_general_ci', $sql);
        $sql = str_replace('utf8mb3', 'utf8mb4', $sql);
        $sql = preg_replace('~^USE `[^`]+`;\s*$~m', '', $sql);

        $statements = $this->splitSql($sql);
        $total = count($statements);
        $this->say('  ' . $total . ' statements');

        $pdo = $this->connect(false);
        $pdo->exec("SET SESSION sql_mode = ''");
        $pdo->exec('SET SESSION foreign_key_checks = 0');
        $pdo->exec('SET SESSION unique_checks = 0');
        $pdo->exec('SET NAMES utf8mb4');
        $this->ensureDatabase($pdo);

        $ok = 0;
        $failed = array();
        foreach ($statements as $idx => $stmt) {
            try {
                $pdo->exec($stmt);
                $ok++;
            } catch (PDOException $e) {
                $short = substr(preg_replace('~\s+~', ' ', $stmt), 0, 90);
                $failed[] = $short . ' -> ' . $e->getMessage();
                if (count($failed) <= 15) {
                    $this->say('    ! ' . $short . ' :: ' . substr($e->getMessage(), 0, 160));
                }
            }
            if ($progressEvery && ($idx % $progressEvery === 0)) {
                $this->say('    ... ' . $idx . '/' . $total);
            }
        }
        $pdo->exec('SET SESSION foreign_key_checks = 1');
        $this->say('  imported statements: ' . $ok . '/' . $total . ', failed: ' . count($failed));
        if ($ok === 0) {
            $this->err('nothing could be imported');
            return false;
        }
        if (count($failed)) {
            $this->say('  ' . count($failed) . ' statement(s) failed - usually harmless (IF NOT EXISTS / duplicate keys)');
        }
        return true;
    }

    /* ---------------------------------------------------------------------
     * 4. database settings & admin account
     * ------------------------------------------------------------------ */

    public function applyServerSettings(PDO $pdo)
    {
        $opening = (int) $this->opt['opening'];
        if ($opening <= 0) {
            $opening = time();
        }
        $defaults = array(
            'SERVER_NAME'   => $this->opt['serverName'],
            'HOMEPAGE'      => $this->opt['homepage'],
            'serverpage'    => $this->opt['host'],
            'OPENING'       => $opening,
            'lastmedal'     => $opening,
            'lastAchiev'    => $opening,
            'lastioasisUpdate' => $opening,
            'ARTEFACTS'     => $opening + 1143200,   // original dump offsets, relative to opening
            'WW_PLAN'       => $opening + 1661600,
            // keep the auto-reinstaller dormat for 10 years (it DROPs every table);
            // set AUTO_REINSTALL=true in application/env.php only if you want it
            'reinstalltime' => $opening + 315360000,
            'SPEED'         => (int) $this->opt['speed'],
            'DEFAULT_GOLD'  => (int) $this->opt['gold'],
            'Lang'          => $this->opt['lang'],
            'adminMail'     => $this->opt['adminEmail'],
            'regstatus'     => (int) $this->opt['regStatus'],
            'HASHASHIAN'    => md5('travian-kit-' . $this->opt['host'] . '-' . $opening . '-' . mt_rand()),
            'ADV_TIME'      => 10,
            'PROTECTIOND'   => 86400,
        );

        // GP_LOCATE must point at the *served* asset folder
        if (!empty($this->opt['gpackUrl'])) {
            $defaults['GP_LOCATE'] = rtrim($this->opt['gpackUrl'], '/') . '/gpack/img_rtl/';
        }

        try {
            $exists = $pdo->query('SELECT COUNT(*) FROM config')->fetchColumn();
        } catch (PDOException $e) {
            $this->err('table "config" is missing - the SQL dump was not imported');
            return false;
        }
        if (!$exists) {
            $this->err('table "config" is empty - is this really a Travian dump?');
            return false;
        }
        $sets = array();
        $vals = array();
        foreach ($defaults as $k => $v) {
            $sets[] = '`' . $k . '` = ?';
            $vals[] = $v;
        }
        $stmt = $pdo->prepare('UPDATE config SET ' . implode(', ', $sets));
        $stmt->execute($vals);
        $this->say('  config table updated (name=' . $this->opt['serverName'] . ', opening=' . date('Y-m-d H:i', $opening) . ')');

        // no leftover activation rows for the future admin
        try {
            $pdo->exec('DELETE FROM activate');
        } catch (PDOException $e) {
            // table may not exist yet
        }
        return true;
    }

    public function applyAdmin(PDO $pdo)
    {
        $user = trim($this->opt['adminUser']);
        $pass = (string) $this->opt['adminPass'];
        $mail = trim($this->opt['adminEmail']);
        if ($user === '' || $pass === '') {
            $this->err('admin user/password missing');
            return false;
        }
        $hash = md5($pass . mb_convert_case($user, MB_CASE_LOWER, 'UTF-8'));

        // prefer the account that already owns a village in the dump
        try {
            $id = (int) $pdo->query('SELECT id FROM users WHERE access >= 8 ORDER BY id LIMIT 1')->fetchColumn();
            if (!$id) {
                $id = (int) $pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
            }
            if (!$id) {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, password, email, tribe, access, gold, lang, lastupdate, regtime, vid, stime, tformat, ok)
                     VALUES (?, ?, ?, 1, 9, ?, ?, ?, ?, 0, 99, 0, 1)'
                );
                $stmt->execute(array($user, $hash, $mail, (int) $this->opt['gold'], $this->opt['lang'], time(), time()));
                $id = (int) $pdo->lastInsertId();
                $this->say('  created a new admin account (no village - build one from the game)');
            } else {
                // free the name if another row uses it
                $pdo->prepare('UPDATE users SET username = CONCAT(username, "_old") WHERE username = ? AND id <> ?')
                    ->execute(array($user, $id));
                $stmt = $pdo->prepare(
                    'UPDATE users SET username = ?, password = ?, email = ?, access = 9, ok = 1, protect = 0, lang = ?, gold = GREATEST(gold, ?)
                     WHERE id = ?'
                );
                $stmt->execute(array($user, $hash, $mail, $this->opt['lang'], (int) $this->opt['gold'], $id));
                $this->say('  admin account updated: id=' . $id . ', user=' . $user);
            }
        } catch (PDOException $e) {
            $this->err('cannot create/update the admin account: ' . $e->getMessage());
            return false;
        }
        $this->adminId = $id;
        return true;
    }

    /* ---------------------------------------------------------------------
     * 5. generated configuration files
     * ------------------------------------------------------------------ */

    public function writeEnv()
    {
        $host = $this->opt['host'];
        $confRoot = $this->confRoot;
        $content = "<?php\n"
            . "/**\n"
            . " * Generated by travian-legend-kit on " . date('Y-m-d H:i:s') . "\n"
            . " * Delete this file and install.lock to run the installer again.\n"
            . " */\n"
            . "define('TRAVIAN_ROOT', '" . $this->escPhp($this->root) . "');\n"
            . "define('TRAVIAN_CONFROOT', '" . $this->escPhp($confRoot) . "');\n"
            . "define('TRAVIAN_DEFAULT_HOST', '" . $this->escPhp($host) . "');\n"
            . "define('GP_IMG_PATH', '" . $this->escPhp($this->gpackImgPath()) . "');\n"
            . "// auto-reinstaller (application/Models/Installertd.php) stays disabled\n"
            . "define('AUTO_REINSTALL', false);\n";
        return $this->write('application/env.php', $content);
    }

    public function escPhp($value)
    {
        return str_replace(array('\\', "'"), array('\\\\', "\\'"), (string) $value);
    }

    public function normalizeHost($host)
    {
        $host = trim((string) $host);
        $host = preg_replace('~^[a-z]+://~i', '', $host);
        $host = preg_replace('~[/?#].*$~', '', $host);
        $host = preg_replace('~:\d+$~', '', $host);
        $host = preg_replace('~^www\.~i', '', $host);
        $host = strtolower($host);
        if (!preg_match('~^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$~', $host)) {
            return '';
        }
        return $host;
    }

    public function gpackImgPath()
    {
        if (!empty($this->opt['gpackImgPath'])) {            return rtrim($this->opt['gpackImgPath'], '/') . '/';
        }
        $candidates = array(
            $this->root . '/gpackcdn/img/',
            dirname($this->root) . '/gpackcdn/img/',
            $this->root . '/img/',
            dirname($this->root) . '/gpack/img/',
            dirname($this->root) . '/domains/gpack.traviana.ir/public_html/img/',
        );
        foreach ($candidates as $c) {
            if (is_dir($c)) {
                return str_replace('\\', '/', $c);
            }
        }
        return str_replace('\\', '/', $this->root . '/img/');
    }

    public function writeBotConfig()
    {
        $content = "<?php\n"
            . "/**\n"
            . " * Telegram-bot / gold-bank database config (generated by travian-legend-kit).\n"
            . " * The shipped copy had a duplicated '<?php' tag -> PHP parse error.\n"
            . " */\n"
            . "date_default_timezone_set('Asia/Tehran');\n"
            . "\$servername = '" . $this->escPhp($this->opt['dbHost']) . "';\n"
            . "\$username   = '" . $this->escPhp($this->opt['dbUser']) . "';\n"
            . "\$password   = '" . $this->escPhp($this->opt['dbPass']) . "';\n"
            . "\$dbname     = '" . $this->escPhp($this->opt['dbName']) . "';\n"
            . "\$conn = @new mysqli(\$servername, \$username, \$password, \$dbname);\n"
            . "if (\$conn->connect_error) {\n"
            . "    error_log('botConfig: ' . \$conn->connect_error);\n"
            . "    \$conn = null;\n"
            . "} else {\n"
            . "    \$conn->set_charset('utf8mb4');\n"
            . "}\n";
        return $this->writeAbs($this->confRoot . '/botConfig.php', $content);
    }

    /** copy hcache / mcache / txt helpers into ConfsData/<host>/ */
    public function prepareConfsData()
    {
        $host = $this->opt['host'];
        $target = $this->confRoot . '/' . $host;
        $templateDir = '';
        foreach (array(
            $this->confRoot . '/tt1.traviana.ir',
            $this->root . '/_setup/ConfsData/tt1.traviana.ir',
            $this->root . '/_setup/ConfsData',
            $this->root . '/ConfsData/tt1.traviana.ir',
            dirname($this->root) . '/ConfsData/tt1.traviana.ir',
        ) as $c) {
            if (is_file($c . '/config.php')) {
                $templateDir = $c;
                break;
            }
        }

        foreach (array('', '/hcache', '/hcache/body', '/hcache/body/330x422', '/mcache', '/logs') as $sub) {
            if (!$this->dryRun) {
                @mkdir($target . $sub, 0775, true);
            }
        }
        foreach (array('queue.txt', 'artefacts.txt', 'ww_plans.txt', 'reinstall.txt', 'test.txt') as $f) {
            if (!is_file($target . '/' . $f) && !$this->dryRun) {
                @file_put_contents($target . '/' . $f, '');
            }
        }

        // copy the shipped hero/map cache files (they are regenerated, but the
        // folder must exist and be writable)
        if ($templateDir !== '' && is_dir($templateDir . '/hcache')) {
            $n = 0;
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($templateDir . '/hcache', FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($it as $f) {
                if (!$f->isFile()) {
                    continue;
                }
                $rel = substr(str_replace('\\', '/', $f->getPathname()), strlen(str_replace('\\', '/', $templateDir)));
                $dst = $target . $rel;
                if (!is_file($dst) && !$this->dryRun) {
                    @mkdir(dirname($dst), 0775, true);
                    @copy($f->getPathname(), $dst);
                    $n++;
                }
            }
            if ($n) {
                $this->say('  copied ' . $n . ' cached avatar/map files');
            }
        }

        // the per-server config template
        $tpl = $templateDir !== '' ? $templateDir . '/config.php' : '';
        $dst = $target . '/config.php';
        if (is_file($dst)) {
            $this->say('  ConfsData/' . $host . '/config.php already exists - kept');
            return true;
        }
        if ($tpl === '' || !is_file($tpl)) {
            $this->err('ConfsData template (tt1.traviana.ir/config.php) not found - copy ConfsData next to the game folder');
            return false;
        }
        $content = file_get_contents($tpl);
        $content = $this->patchServerConfig($content);
        return $this->writeAbs($dst, $content);
    }

    /** rewrite DB credentials, asset URLs and the known PHP-8 traps in a server config */
    public function patchServerConfig($content)
    {
        $db = array(
            'SQL_SERVER' => $this->opt['dbHost'],
            'SQL_USER'   => $this->opt['dbUser'],
            'SQL_PASS'   => $this->opt['dbPass'],
            'SQL_DB'     => $this->opt['dbName'],
        );
        foreach ($db as $const => $value) {
            $content = preg_replace(
                '~define\(\s*[\'"]' . $const . '[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*\)\s*;+~',
                "define('" . $const . "', '" . $this->escPhp($value) . "');",
                $content
            );
        }

        $base = !empty($this->opt['gpackUrl']) ? rtrim($this->opt['gpackUrl'], '/') . '/' : '/gpackcdn/';
        $map = array(
            'GP_LOCATION'   => $base . 'gpack/',
            'GP_LOCATION1'  => $base . 'js/',
            'GP_LOCATION2'  => $base . 'img/',
            'GP_LOCATIONALL' => $base,
        );
        foreach ($map as $const => $value) {
            $content = preg_replace(
                '~define\(\s*[\'"]' . $const . '[\'"]\s*,\s*[\'"][^\'"]*[\'"]\s*\)\s*;+~',
                "define('" . $const . "', '" . $this->escPhp($value) . "');",
                $content
            );
        }

        // division by zero as soon as ADV_TIME is 0 in the DB
        $content = str_replace(
            'define("ADV_TIME",86400/$sData[\'ADV_TIME\']);',
            'define("ADV_TIME", 86400 / max(1, (int) $sData[\'ADV_TIME\']));',
            $content
        );

        // only warnings, but they print on every page with PHP 8
        $content = str_replace(
            'error_reporting(E_ALL );',
            'error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);',
            $content
        );

        // buyfeature may be empty -> "Trying to access array offset on value of type bool"
        $content = str_replace(
            '$ppData = $db->query("SELECT * FROM buyfeature ")[0];',
            "\$__buy = \$db->query(\"SELECT * FROM buyfeature \");\n"
            . "\$ppData = (is_array(\$__buy) && isset(\$__buy[0])) ? \$__buy[0] : array();",
            $content
        );
        // default keys must exist before the foreach that fills them, otherwise
        // PHP 8 prints "Undefined array key" / "foreach() on null" on every page
        $content = preg_replace(
            '~\$confing\s*=\s*\$ppData;\s*\$sagdata\s*=\s*array\(\);~',
            '$confing = $ppData;' . "\n"
            . '$sagdata = array();' . "\n"
            . 'foreach (array(\'generals\', \'buyfeatures\', \'buypacks\', \'buyothers\', \'menus\') as $__k) {' . "\n"
            . '    $sagdata[$__k] = array();' . "\n"
            . '}',
            $content
        );

        // duplicate define() -> "Constant already defined" warning
        $content = preg_replace(
            '~\ndefine\("PLUS_TIME",\$sData\[\'PLUS_TIME\'\]\);~',
            "\n// define(\"PLUS_TIME\", ...) duplicate removed by installer",
            $content,
            1
        );
        return $content;
    }

    /** optional: copy gpack/{gpack,js,img} so the game does not depend on a dead CDN */
    public function copyGpack()
    {
        // the cPanel package already ships gpackcdn/{gpack,js,img}
        if (is_dir($this->p('gpackcdn/gpack')) || is_dir($this->p('gpackcdn/img'))) {
            $this->say('  gpack assets already in gpackcdn/ - nothing to copy');
            return true;
        }
        if ($this->opt['gpack'] === 'skip') {
            $this->say('  gpack copy skipped');
            return false;
        }
        $src = $this->opt['gpack'];
        if ($src === 'auto' || $src === '') {
            $candidates = array(
                dirname($this->root) . '/domains/gpack.traviana.ir/public_html',
                dirname($this->root) . '/gpack.traviana.ir/public_html',
                dirname($this->root) . '/gpack',
                $this->root . '/gpacksrc',
            );
            $src = '';
            foreach ($candidates as $c) {
                if (is_dir($c . '/gpack') || is_dir($c . '/img')) {
                    $src = $c;
                    break;
                }
            }
        }
        if ($src === '' || !is_dir($src)) {
            $this->say('  gpack assets not found - keeping the URLs you entered (set --gpack=/path or copy them manually to <root>/gpackcdn/)');
            return false;
        }
        $dst = $this->root . '/gpackcdn';
        $this->say('  copying gpack assets ' . $src . ' -> ' . $dst);
        $copied = 0;
        foreach (array('gpack', 'js', 'img') as $sub) {
            $from = rtrim($src, '/') . '/' . $sub;
            if (!is_dir($from)) {
                continue;
            }
            $copied += $this->copyTree($from, $dst . '/' . $sub);
        }
        $this->say('  copied ' . $copied . ' asset files');
        return true;
    }

    public function copyTree($from, $to)
    {
        $n = 0;
        $from = rtrim(str_replace('\\', '/', $from), '/');
        if (!is_dir($to) && !$this->dryRun) {
            @mkdir($to, 0775, true);
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $item) {
            $target = $to . substr(str_replace('\\', '/', $item->getPathname()), strlen($from));
            if ($item->isDir()) {
                if (!$this->dryRun) {
                    @mkdir($target, 0775, true);
                }
                continue;
            }
            if (is_file($target)) {
                continue;
            }
            if ($this->dryRun) {
                $n++;
                continue;
            }
            if (@copy($item->getPathname(), $target)) {
                $n++;
            }
        }
        return $n;
    }

    /* ---------------------------------------------------------------------
     * 6. orchestration
     * ------------------------------------------------------------------ */

    public function runAll(array $steps = array())
    {
        $host = $this->normalizeHost($this->opt['host']);
        if ($host === '') {
            $this->err('invalid domain in --host (' . $this->opt['host'] . ')');
            return false;
        }
        $this->opt['host'] = $host;
        if (empty($this->opt['homepage'])) {
            $this->opt['homepage'] = 'http://' . $host;
        }
        if (empty($this->opt['gpackUrl'])) {
            $this->opt['gpackUrl'] = 'http://' . $host . '/gpackcdn/';
        }

        $this->say('Travian Legend installer');
        $this->say('  site root : ' . $this->root);
        $this->say('  conf root : ' . $this->confRoot);
        $this->say('  PHP       : ' . PHP_VERSION . ' (' . PHP_SAPI . ')');

        if (!$this->preflight()) {
            $this->say('');
            $this->say('Preflight failed:');
            foreach ($this->checks as $c) {
                if (!$c[1]) {
                    $this->say('  - ' . $c[0] . ' (' . $c[3] . ')');
                }
            }
            foreach ($this->errors as $e) {
                $this->say('  ! ' . $e);
            }
            return false;
        }
        $this->say('  preflight: OK');
        $this->say('');

        // ---- 1. code fixes -------------------------------------------------
        $this->say('[1/6] repairing the source tree');
        $this->fixBrokenHeaders();
        $this->writeLegacyCompat();
        $this->writeLangFallback();
        $this->fixApplicationConfig();
        $this->fixDbClass();
        $this->fixLoginBackdoor();
        $this->fixCliSessionForge();
        $this->fixAutoReinstaller();
        $this->fixBrokenIncludes();
        $this->fixOrphans();
        $this->removeDebugFiles();
        $this->writeHtaccess();

        // ---- 2. database ---------------------------------------------------
        $this->say('');
        $this->say('[2/6] database');
        $sqlFile = $this->findSqlFile();
        if ($sqlFile === '') {
            $this->err('no .sql dump found');
            return false;
        }
        $this->say('  dump: ' . $sqlFile);

        $hasTables = false;
        try {
            $pdo = $this->connect(false);
            $this->ensureDatabase($pdo);
            $pdo->exec("SET SESSION sql_mode = ''");
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            $hasTables = count($tables) > 10;
            $this->say('  connected, existing tables: ' . count($tables));
        } catch (PDOException $e) {
            $this->err('connection failed: ' . $e->getMessage());
            return false;
        }

        if ($hasTables && !$this->opt['force']) {
            $this->say('  database already has tables - import skipped (use --force to wipe & re-import)');
        } else {
            if ($hasTables) {
                $this->say('  --force: dropping all tables');
                foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
                    $pdo->exec('DROP TABLE `' . str_replace('`', '', $t) . '`');
                }
            }
            if (!$this->importSql($sqlFile)) {
                return false;
            }
            // the cPanel package ships the dump inside _setup/ - drop it once imported
            if (!$this->dryRun
                && strpos(str_replace('\\', '/', $sqlFile), '/_setup/') !== false
                && is_file($sqlFile)) {
                @unlink($sqlFile);
                $this->say('  deleted the bundled dump (_setup/' . basename($sqlFile) . ') - import already done');
            }
        }

        // ---- 3. server settings -------------------------------------------
        $this->say('');
        $this->say('[3/6] server settings');
        $pdo = $this->connect(true);
        if (!$this->applyServerSettings($pdo)) {
            return false;
        }

        // ---- 4. admin ------------------------------------------------------
        $this->say('');
        $this->say('[4/6] admin account');
        if (!$this->applyAdmin($pdo)) {
            return false;
        }

        // ---- 5. assets + config --------------------------------------------
        $this->say('');
        $this->say('[5/6] configuration files');
        $this->copyGpack();
        if (!$this->prepareConfsData()) {
            return false;
        }
        $this->writeBotConfig();
        $this->scrubSecrets();
        $this->writeEnv();

        // ---- 6. lock -------------------------------------------------------
        $this->say('');
        $this->say('[6/6] finishing');
        $this->write('install.lock', 'installed ' . date('c') . ' by travian-legend-kit');

        $this->say('');
        $this->say('Done.');
        $this->say('  game   : http://' . ($this->opt['host'] !== '' ? $this->opt['host'] : 'your-domain') . '/login.php');
        $this->say('  admin  : ' . $this->opt['adminUser']);
        $this->say('  panel  : ./TdPaneLXP/');
        $this->say('  delete install.php now!');
        return count($this->errors) === 0;
    }

    /** post-install verification (--check / ?mode=check) */
    public function checkHealth()
    {
        $rows = array();

        $confFile = $this->confRoot . '/' . $this->opt['host'] . '/config.php';
        $rows[] = array('ConfsData/<domain>/config.php', is_file($confFile), $confFile);
        $rows[] = array('application/env.php', is_file($this->p('application/env.php')), 'paths + default host');
        $rows[] = array('application/legacy_compat.php', is_file($this->p('application/legacy_compat.php')), 'mysql_* shim + TB_PREFIX');
        $rows[] = array('application/lang_fallback.php', is_file($this->p('application/lang_fallback.php')), '102 UI constants');
        $rows[] = array('install.lock', is_file($this->p('install.lock')), 'delete install.php after checking');

        $cfg = $this->p('application/config.php');
        $portable = false;
        if (is_file($cfg)) {
            $s = file_get_contents($cfg);
            $portable = (strpos($s, 'TRAVIAN_CONFROOT') !== false);
        }
        $rows[] = array('application/config.php portable', $portable, $portable ? 'no /home/<user> paths' : 'still the original file - run --fix-only');

        // is the layout template repaired? (the 12-file bug)
        $html = $this->p('application/views/html.php');
        $fixed = false;
        $note = 'missing file';
        if (is_file($html)) {
            $s = substr(file_get_contents($html), 0, 1500);
            $close = strpos($s, '?>');
            $doc = strpos($s, '<!DOCTYPE');
            $fixed = ($close !== false && $doc !== false && $close < $doc);
            $note = $fixed ? 'html.php leaves PHP mode before <!DOCTYPE>' : 'still a parse error - run --fix-only';
        }
        $rows[] = array('licence-header repair', $fixed, $note);

        $gpOk = is_dir($this->p('gpackcdn')) || is_dir(rtrim($this->gpackImgPath(), '/'));
        $rows[] = array('gpack assets', $gpOk, $this->p('gpackcdn'));
        $rows[] = array('logs/ writable', is_dir($this->p('logs')) ? is_writable($this->p('logs')) : is_writable($this->root), 'db-errors.log');
        $rows[] = array('ConfsData writable', is_writable($this->confRoot), 'queue.txt / hcache / mcache');

        // database
        try {
            $pdo = $this->connect(true);
            $n = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
            $rows[] = array('database tables', $n > 50, $n . ' tables in ' . $this->opt['dbName']);

            $c = $pdo->query('SELECT SERVER_NAME, OPENING, SPEED FROM config LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            if ($c) {
                $rows[] = array('config row', true, 'name=' . $c['SERVER_NAME'] . ', speed=' . $c['SPEED'] . ', opening=' . date('Y-m-d H:i', (int) $c['OPENING']));
            } else {
                $rows[] = array('config row', false, 'empty - import the dump');
            }

            $a = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE access >= 9')->fetchColumn();
            $rows[] = array('admin account', $a > 0, $a . ' account(s) with access>=9');
        } catch (PDOException $e) {
            $rows[] = array('database connection', false, $e->getMessage());
        }

        return $rows;
    }

    /** repair mode (no database): install.php?mode=fix  |  php install.php --fix-only */
    public function runFixesOnly()
    {
        $this->say('Travian Legend - repair mode');
        $this->say('  site root : ' . $this->root);
        $n = 0;
        $n += $this->fixBrokenHeaders();
        $n += (int) $this->writeLegacyCompat();
        $n += (int) $this->writeLangFallback();
        $n += (int) $this->fixApplicationConfig();
        $n += (int) $this->fixDbClass();
        $n += (int) $this->fixLoginBackdoor();
        $n += (int) $this->fixCliSessionForge();
        $n += (int) $this->fixAutoReinstaller();
        $n += (int) $this->fixBrokenIncludes();
        $n += $this->fixOrphans();
        $n += $this->removeDebugFiles();
        $n += (int) $this->writeHtaccess();
        $this->say('');
        $this->say('repairs applied: ' . $n . ' file change(s)');
        return true;
    }
}


/* ====================================================================== *
 *  CLI / WEB front-end
 * ====================================================================== */

/**
 * Travian Legend - easy installer (web + CLI)
 * -------------------------------------------------------------
 * WEB :  http://your-domain/install.php
 *         http://your-domain/install.php?mode=fix      (repair only)
 * CLI :  php install.php --db-user=root --db-pass=secret --db-name=travian \
 *                        --host=game.example.com --admin-user=admin \
 *                        --admin-pass=secret --admin-email=me@example.com
 *         php install.php --fix-only [--dry-run]
 *
 * Self-contained: this is the only file you upload. The installer class and the
 * legacy_compat.php payload (mysql_* shim) live inside this file. Put it in the
 * same folder as login.php.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
@set_time_limit(0);
@ini_set('memory_limit', '512M');

$isCli = (PHP_SAPI === 'cli');

/* =====================================================================
 * CLI
 * ================================================================== */
if ($isCli) {
    $args = array();
    $flags = array();
    foreach (array_slice($argv, 1) as $a) {
        if (substr($a, 0, 2) === '--') {
            $a = substr($a, 2);
            if (strpos($a, '=') !== false) {
                list($k, $v) = explode('=', $a, 2);
                $args[$k] = $v;
            } else {
                $flags[$a] = true;
            }
        }
    }
    if (isset($flags['help']) || isset($args['help'])) {
        echo "Travian Legend installer\n\n";
        echo "  --site-root=/path/to/game        folder that contains login.php\n";
        echo "  --conf-root=/path/to/ConfsData   default: ../ConfsData\n";
        echo "  --db-host=localhost --db-port=3306 --db-user= --db-pass= --db-name=\n";
        echo "  --sql=/path/to/dump.sql\n";
        echo "  --host=game.example.com          domain of the game\n";
        echo "  --server-name=\"My Server\"\n";
        echo "  --homepage=https://game.example.com\n";
        echo "  --speed=3 --lang=fa --gold=100000 --opening=<unixtime>\n";
        echo "  --admin-user=admin --admin-pass=secret --admin-email=me@example.com\n";
        echo "  --gpack=auto|skip|/path/to/gpack.traviana.ir/public_html\n";
        echo "  --gpack-url=https://game.example.com/gpackcdn/\n";
        echo "  --fix-only         repair the tree, do not touch the database\n";
        echo "  --check            verify an existing installation (needs --db-*)\n";
        echo "  --dry-run          show what would change\n";
        echo "  --force            re-import / re-install over an existing setup\n";
        exit(0);
    }

    $inst = new TravianInstaller(
        isset($args['site-root']) ? $args['site-root'] : null,
        isset($args['conf-root']) ? $args['conf-root'] : (isset($args['confsdata']) ? $args['confsdata'] : null)
    );
    $inst->dryRun = isset($flags['dry-run']);

    if (isset($flags['fix-only'])) {
        exit($inst->runFixesOnly() ? 0 : 1);
    }

    $map = array(
        'db-host' => 'dbHost', 'db-port' => 'dbPort', 'db-user' => 'dbUser',
        'db-pass' => 'dbPass', 'db-name' => 'dbName',
        'host' => 'host', 'server-name' => 'serverName', 'homepage' => 'homepage',
        'speed' => 'speed', 'lang' => 'lang', 'gold' => 'gold', 'opening' => 'opening',
        'admin-user' => 'adminUser', 'admin-pass' => 'adminPass', 'admin-email' => 'adminEmail',
        'gpack' => 'gpack', 'gpack-url' => 'gpackUrl', 'reg' => 'regStatus',
    );
    foreach ($map as $cli => $prop) {
        if (isset($args[$cli])) {
            $inst->opt[$prop] = $args[$cli];
        }
    }
    if (isset($args['sql'])) {
        $inst->opt['sqlFile'] = $args['sql'];
    }
    if (isset($flags['force']) || isset($args['force'])) {
        $inst->opt['force'] = true;
    }
    if (isset($flags['no-protect'])) {
        $inst->opt['protect'] = false;
    }

    /* defaults that need env */
    if (empty($inst->opt['dbUser'])) {
        $inst->opt['dbUser'] = 'root';
    }
    if (empty($inst->opt['adminPass'])) {
        $inst->opt['adminPass'] = bin2hex(random_bytes(6));
        echo "!! generated admin password: " . $inst->opt['adminPass'] . "\n";
    }
    if (empty($inst->opt['host'])) {
        $inst->opt['host'] = 'localhost';
    }
    if (empty($inst->opt['homepage'])) {
        $inst->opt['homepage'] = 'http://' . $inst->opt['host'];
    }
    if (empty($inst->opt['adminEmail'])) {
        $inst->opt['adminEmail'] = 'admin@' . $inst->opt['host'];
    }
    if ($inst->opt['opening'] === 0 || $inst->opt['opening'] === '') {
        $inst->opt['opening'] = time();
    }

    if (isset($flags['check'])) {
        $rows = $inst->checkHealth();
        $bad = 0;
        echo "\n== health check ==\n";
        foreach ($rows as $r) {
            if (!$r[1]) {
                $bad++;
            }
            printf("  %-6s %-38s %s\n", $r[1] ? 'OK' : 'FAIL', $r[0], $r[2]);
        }
        echo "\n" . ($bad === 0 ? 'everything looks fine' : $bad . ' problem(s)') . "\n";
        exit($bad === 0 ? 0 : 1);
    }

    $ok = $inst->runAll();
    echo "\n" . ($ok ? 'INSTALL COMPLETE' : 'INSTALL FAILED') . "\n";
    if (!$ok) {
        foreach ($inst->errors as $e) {
            echo ' - ' . $e . "\n";
        }
    }
    exit($ok ? 0 : 1);
}

/* =====================================================================
 * WEB
 * ================================================================== */
session_start();
if (empty($_SESSION['tk'])) {
    $_SESSION['tk'] = bin2hex(random_bytes(8));
}
$tk = $_SESSION['tk'];

function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

$inst = new TravianInstaller();
$inst->preflight();

/* ---- repair-only: install.php?mode=fix [&dry=1] ------------------------- */
if (isset($_GET['mode']) && $_GET['mode'] === 'fix') {
    $inst->dryRun = (isset($_GET['dry']) && $_GET['dry'] === '1');
    $inst->runFixesOnly();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8">'
        . '<title>تعمیر فایل‌ها</title></head>'
        . '<body style="background:#11151c;color:#e8edf3;font:14px Tahoma,sans-serif;padding:24px;line-height:1.8">'
        . '<h1 style="color:#f0a93b;font-size:20px">تعمیر فایل‌ها (بدون دیتابیس)</h1>'
        . '<p style="color:#93a1b1">پوشه بازی: <code>' . h($inst->root) . '</code></p>'
        . '<pre style="background:#0b0f15;border:1px solid #2b3441;border-radius:8px;padding:14px;'
        . 'white-space:pre-wrap;word-break:break-word;direction:ltr;text-align:left;font:12px Consolas,monospace">'
        . h(implode("\n", $inst->log)) . '</pre>'
        . '<p><a href="install.php" style="color:#f0a93b">بازگشت به نصاب</a></p></body></html>';
    exit;
}

/* ---- health check: install.php?mode=check&dbUser=..&dbPass=..&dbName=.. --- */
if (isset($_GET['mode']) && $_GET['mode'] === 'check') {
    $inst->opt['dbHost'] = isset($_GET['dbHost']) ? $_GET['dbHost'] : 'localhost';
    $inst->opt['dbPort'] = isset($_GET['dbPort']) ? (int) $_GET['dbPort'] : 3306;
    $inst->opt['dbUser'] = isset($_GET['dbUser']) ? $_GET['dbUser'] : '';
    $inst->opt['dbPass'] = isset($_GET['dbPass']) ? $_GET['dbPass'] : '';
    $inst->opt['dbName'] = isset($_GET['dbName']) ? $_GET['dbName'] : 'travian';
    $inst->opt['host'] = $inst->normalizeHost(isset($_GET['host']) && $_GET['host'] !== ''
        ? $_GET['host']
        : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'));
    $rows = $inst->checkHealth();
    $bad = 0;
    foreach ($rows as $r) {
        if (!$r[1]) {
            $bad++;
        }
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>بررسی نصب</title></head>'
        . '<body style="background:#11151c;color:#e8edf3;font:14px Tahoma,sans-serif;padding:24px">'
        . '<h1 style="color:#f0a93b;font-size:20px">بررسی سلامت نصب</h1><table style="border-collapse:collapse">';
    foreach ($rows as $r) {
        $color = $r[1] ? '#63e6a1' : '#ff8b8b';
        echo '<tr><td style="padding:6px 12px;border-bottom:1px solid #2b3441;color:' . $color . ';font-weight:700">'
            . ($r[1] ? 'OK' : 'FAIL') . '</td>'
            . '<td style="padding:6px 12px;border-bottom:1px solid #2b3441">' . h($r[0]) . '</td>'
            . '<td style="padding:6px 12px;border-bottom:1px solid #2b3441;color:#93a1b1;direction:ltr">'
            . h($r[2]) . '</td></tr>';
    }
    echo '</table><p>' . ($bad === 0 ? 'همه‌چیز سالم است.' : $bad . ' مورد مشکل دارد.') . '</p>'
        . '<p><a href="install.php" style="color:#f0a93b">بازگشت</a></p></body></html>';
    exit;
}

$posted = ($_SERVER['REQUEST_METHOD'] === 'POST');
$done = false;
$fatal = false;

$form = array(
    'dbHost' => isset($_POST['dbHost']) ? $_POST['dbHost'] : 'localhost',
    'dbPort' => isset($_POST['dbPort']) ? $_POST['dbPort'] : '3306',
    'dbUser' => isset($_POST['dbUser']) ? $_POST['dbUser'] : '',
    'dbPass' => isset($_POST['dbPass']) ? $_POST['dbPass'] : '',
    'dbName' => isset($_POST['dbName']) ? $_POST['dbName'] : 'travian',
    'host' => isset($_POST['host']) ? $_POST['host'] : (isset($_SERVER['HTTP_HOST']) ? preg_replace('~^www\.~', '', $_SERVER['HTTP_HOST']) : 'localhost'),
    'serverName' => isset($_POST['serverName']) ? $_POST['serverName'] : 'Travian Legend',
    'homepage' => isset($_POST['homepage']) ? $_POST['homepage'] : '',
    'speed' => isset($_POST['speed']) ? $_POST['speed'] : '3',
    'lang' => isset($_POST['lang']) ? $_POST['lang'] : 'fa',
    'gold' => isset($_POST['gold']) ? $_POST['gold'] : '100000',
    'opening' => isset($_POST['opening']) ? $_POST['opening'] : date('Y-m-d\TH:i'),
    'adminUser' => isset($_POST['adminUser']) ? $_POST['adminUser'] : 'admin',
    'adminPass' => isset($_POST['adminPass']) ? $_POST['adminPass'] : '',
    'adminEmail' => isset($_POST['adminEmail']) ? $_POST['adminEmail'] : '',
    'gpack' => isset($_POST['gpack']) ? $_POST['gpack'] : 'auto',
    'gpackUrl' => isset($_POST['gpackUrl']) ? $_POST['gpackUrl'] : '',
    'protect' => isset($_POST['protect']) ? 1 : 0,
    'force' => isset($_POST['force']) ? 1 : 0,
);

if ($posted) {
    if (!isset($_POST['tk']) || !hash_equals($tk, (string) $_POST['tk'])) {
        $inst->err('invalid form token - reload the page');
        $fatal = true;
    }
    if (!$fatal) {
        $inst->opt['dbHost'] = $form['dbHost'];
        $inst->opt['dbPort'] = (int) $form['dbPort'];
        $inst->opt['dbUser'] = $form['dbUser'];
        $inst->opt['dbPass'] = $form['dbPass'];
        $inst->opt['dbName'] = $form['dbName'];
        $inst->opt['host'] = strtolower(preg_replace('~^www\.~', '', trim($form['host'])));
        $inst->opt['serverName'] = $form['serverName'];
        $inst->opt['homepage'] = $form['homepage'] !== '' ? $form['homepage'] : 'http://' . $inst->opt['host'];
        $inst->opt['speed'] = (int) $form['speed'];
        $inst->opt['lang'] = $form['lang'];
        $inst->opt['gold'] = (int) $form['gold'];
        $inst->opt['opening'] = $form['opening'] !== '' ? strtotime($form['opening']) : time();
        $inst->opt['adminUser'] = $form['adminUser'];
        $inst->opt['adminPass'] = $form['adminPass'];
        $inst->opt['adminEmail'] = $form['adminEmail'] !== '' ? $form['adminEmail'] : 'admin@' . $inst->opt['host'];
        $inst->opt['gpack'] = $form['gpack'];
        $inst->opt['gpackUrl'] = $form['gpackUrl'] !== '' ? $form['gpackUrl'] : ('http' . (!empty($_SERVER['HTTPS']) ? 's' : '') . '://' . $inst->opt['host'] . '/gpackcdn/');
        $inst->opt['protect'] = (bool) $form['protect'];
        $inst->opt['force'] = (bool) $form['force'];

        $done = $inst->runAll();
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>نصب تراوین لجند</title>
<style>
:root{--bg:#11151c;--card:#1a2029;--line:#2b3441;--txt:#e8edf3;--muted:#93a1b1;--ok:#2ecc71;--bad:#e74c3c;--acc:#f0a93b}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--txt);font:14px/1.7 Tahoma,"Segoe UI",sans-serif}
.wrap{max-width:960px;margin:0 auto;padding:24px 16px 64px}
h1{font-size:22px;margin:0 0 4px}
h2{font-size:16px;margin:28px 0 10px;color:var(--acc)}
.sub{color:var(--muted);font-size:13px;margin-bottom:18px}
.card{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:16px;margin-bottom:16px}
table{width:100%;border-collapse:collapse}
td{padding:5px 6px;border-bottom:1px solid var(--line);vertical-align:top}
td:first-child{width:42%;color:var(--muted)}
.ok{color:var(--ok)}.bad{color:var(--bad)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 14px}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px 14px}
label{display:block;font-size:12px;color:var(--muted);margin-bottom:4px}
input[type=text],input[type=password],input[type=number],input[type=email],input[type=datetime-local],select{
width:100%;padding:8px 10px;background:#0e131a;border:1px solid var(--line);border-radius:7px;color:var(--txt);font-family:inherit}
input[type=checkbox]{transform:scale(1.15);margin-inline-end:6px}
button{margin-top:18px;width:100%;padding:12px;border:0;border-radius:8px;background:var(--acc);color:#1a1206;font-weight:700;font-size:15px;cursor:pointer}
button:hover{filter:brightness(1.08)}
pre{background:#0b0f15;border:1px solid var(--line);border-radius:8px;padding:12px;max-height:420px;overflow:auto;
white-space:pre-wrap;word-break:break-word;font:12px/1.6 Consolas,monospace;direction:ltr;text-align:left}
.note{font-size:12px;color:var(--muted)}
.err{background:#2a1416;border-color:#54262a}
.flag{display:inline-block;padding:1px 7px;border-radius:20px;font-size:11px}
.flag.ok{background:#12321f;color:#63e6a1}.flag.bad{background:#3a1719;color:#ff8b8b}
</style>
</head>
<body>
<div class="wrap">
  <h1>نصب‌کننده تراوین لجند</h1>
  <div class="sub">
    پکیج: <code>hamid travian</code> — موتور بازی: <code>domains/tt1</code> — کانفیگ: <code>ConfsData/</code>
  </div>

  <?php if ($inst->isInstalled() && !$posted): ?>
    <div class="card err">
      <b>این نسخه قبلاً نصب شده است</b> (<code>install.lock</code> موجود است).<br>
      اگر می‌خواهید دوباره نصب کنید، فایل <code>install.lock</code> و <code>application/env.php</code> را حذف کنید
      یا گزینه «نصب مجدد» را در پایین تیک بزنید. برای پاک کردن نصاب هم پس از پایان، همین فایل را حذف کنید.
    </div>
  <?php endif; ?>

  <h2>۱) بررسی پیش‌نیازها</h2>
  <div class="card">
    <table>
      <?php foreach ($inst->checks as $c): ?>
      <tr>
        <td><?= h($c[0]) ?></td>
        <td>
          <span class="flag <?= $c[1] ? 'ok' : 'bad' ?>"><?= $c[1] ? 'OK' : ($c[2] ? 'ناموفق' : 'اختیاری') ?></span>
          <span class="note"><?= h($c[3]) ?></span>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <p class="note">
      مسیر بازی: <code><?= h($inst->root) ?></code><br>
      مسیر ConfsData: <code><?= h($inst->confRoot) ?></code><br>
      فایل SQL: <code><?= h($inst->findSqlFile() !== '' ? $inst->findSqlFile() : 'پیدا نشد') ?></code>
    </p>
  </div>

  <?php if ($posted && !$done): ?>
    <h2>خطا</h2>
    <div class="card err">
      <ul>
        <?php foreach ($inst->errors as $e): ?>
          <li><?= h($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($done): ?>
    <h2>گزارش نصب</h2>
    <div class="card"><pre><?= h(implode("\n", $inst->log)) ?></pre></div>
    <div class="card">
      <b>نصب کامل شد.</b>
      <p>
        ورود بازی: <a href="login.php" style="color:var(--acc)">login.php</a><br>
        نام کاربری مدیر: <code><?= h($inst->opt['adminUser']) ?></code><br>
        پنل مدیریت: <code>TdPaneLXP/</code>
      </p>
      <p class="note">
        همین حالا فایل <code>install.php</code> را از سرور حذف کنید.
        فایل <code>install.lock</code> را نگه دارید تا نصاب دوباره اجرا نشود.
      </p>
    </div>
  <?php else: ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="tk" value="<?= h($tk) ?>">

    <h2>۲) دیتابیس</h2>
    <div class="card">
      <div class="grid3">
        <div><label>Host</label><input type="text" name="dbHost" value="<?= h($form['dbHost']) ?>"></div>
        <div><label>Port</label><input type="number" name="dbPort" value="<?= h($form['dbPort']) ?>"></div>
        <div><label>Database</label><input type="text" name="dbName" value="<?= h($form['dbName']) ?>"></div>
      </div>
      <div class="grid" style="margin-top:10px">
        <div><label>User</label><input type="text" name="dbUser" value="<?= h($form['dbUser']) ?>"></div>
        <div><label>Password</label><input type="password" name="dbPass" value="<?= h($form['dbPass']) ?>"></div>
      </div>
      <p class="note">اگر دیتابیس وجود نداشته باشد ساخته می‌شود. جدول‌ها از فایل <code>.sql</code> داخل پکیج وارد می‌شوند.</p>
    </div>

    <h2>۳) تنظیمات سرور</h2>
    <div class="card">
      <div class="grid">
        <div><label>دامنه بازی (SERVER_NAME)</label><input type="text" name="host" value="<?= h($form['host']) ?>"></div>
        <div><label>نام نمایشی سرور</label><input type="text" name="serverName" value="<?= h($form['serverName']) ?>"></div>
        <div><label>آدرس اصلی (HOMEPAGE)</label><input type="text" name="homepage" value="<?= h($form['homepage']) ?>"></div>
        <div><label>زمان شروع بازی</label><input type="datetime-local" name="opening" value="<?= h($form['opening']) ?>"></div>
        <div><label>سرعت (SPEED)</label><input type="number" name="speed" value="<?= h($form['speed']) ?>"></div>
        <div><label>زبان</label>
          <select name="lang">
            <?php foreach (array('fa' => 'فارسی', 'en' => 'English', 'ar' => 'العربية', 'de' => 'Deutsch') as $k => $v): ?>
              <option value="<?= h($k) ?>" <?= $form['lang'] === $k ? 'selected' : '' ?>><?= h($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label>طلای اولیه هر بازیکن</label><input type="number" name="gold" value="<?= h($form['gold']) ?>"></div>
        <div><label>آدرس پکیج گرافیک (GP_LOCATION)</label><input type="text" name="gpackUrl" value="<?= h($form['gpackUrl']) ?>" placeholder="https://<?= h($form['host']) ?>/gpackcdn/"></div>
      </div>
      <div style="margin-top:12px">
        <label>پوشه گرافیک (gpack)</label>
        <select name="gpack">
          <option value="auto" <?= $form['gpack'] === 'auto' ? 'selected' : '' ?>>خودکار (از پکیج پیدا و کپی شود)</option>
          <option value="skip" <?= $form['gpack'] === 'skip' ? 'selected' : '' ?>>کپی نکن (از CDN/آدرس دلخواه استفاده کن)</option>
        </select>
      </div>
    </div>

    <h2>۴) اکانت مدیر</h2>
    <div class="card">
      <div class="grid3">
        <div><label>نام کاربری</label><input type="text" name="adminUser" value="<?= h($form['adminUser']) ?>"></div>
        <div><label>رمز عبور</label><input type="password" name="adminPass" value="<?= h($form['adminPass']) ?>"></div>
        <div><label>ایمیل</label><input type="email" name="adminEmail" value="<?= h($form['adminEmail']) ?>"></div>
      </div>
      <p class="note">اکانت موجود در دامپ که روستا دارد بازنویسی می‌شود؛ پس از نصب با همین اطلاعات وارد شوید.</p>
    </div>

    <h2>۵) گزینه‌ها و اجرا</h2>
    <div class="card">
      <label><input type="checkbox" name="protect" value="1" <?= $form['protect'] ? 'checked' : '' ?>>
        محافظت پوشه‌های <code>application</code> و <code>ConfsData</code> با htaccess</label>
      <label><input type="checkbox" name="force" value="1" <?= $form['force'] ? 'checked' : '' ?>>
        نصب مجدد / بازنویسی جدول‌ها (force)</label>
      <button type="submit">شروع نصب</button>
    </div>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
