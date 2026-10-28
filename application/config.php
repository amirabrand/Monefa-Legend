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