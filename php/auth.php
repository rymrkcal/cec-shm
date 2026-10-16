<?php
// Allow this page only when an admin is logged in.

session_start();

if (!isset($_SESSION['role'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session_guard.php';
reject_stale_session($conn, '../index.php'); //check if the password is valid

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../client/home.php');
    exit;
}

