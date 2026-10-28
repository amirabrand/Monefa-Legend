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