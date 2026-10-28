<?php
/**
 * Monefa Legend — Telegram bot / gold-bank DB config (TEMPLATE)
 * Real credentials are written automatically by install_ubuntu.sh / install.php
 *
 * Placeholders used:  {{DB_USER}}  {{DB_PASS}}  {{DB_NAME}}
 */
date_default_timezone_set('Asia/Tehran');
$servername = 'localhost';
$username   = '{{DB_USER}}';
$password   = '{{DB_PASS}}';
$dbname     = '{{DB_NAME}}';
$conn = @new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log('botConfig: ' . $conn->connect_error);
    $conn = null;
} else {
    $conn->set_charset('utf8mb4');
}
