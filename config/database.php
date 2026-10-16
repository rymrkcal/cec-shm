<?php
$db_host = '127.0.0.1';
$db_user = 'root';
$db_password = '';
$database = 'cec_shm';

if (defined('DB_SETTINGS_ONLY')) {
    return;
}

if (isset($conn) && $conn instanceof mysqli) { //
    return;
}

$conn = mysqli_connect($db_host, $db_user, $db_password, $database); //connection onthe database. if mysql is down. it stops 
if (!$conn) {
    die('Could not connect to the database.');
}

