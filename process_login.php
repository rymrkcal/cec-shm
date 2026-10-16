<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require 'config/database.php'; // if already log in check the database
require 'php/session_guard.php'; //confirms if the password still valid
ensure_session_version_column($conn);

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$sql = 'SELECT user_id, username, password_hash, role, session_version FROM users WHERE username = ?'; //check this from the database/
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($user && password_verify($password, $user['password_hash'])) {  // confirms if the password and username correct
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['session_version'] = (int) $user['session_version'];

    if ($user['role'] === 'admin') { //if that role admin, open the admin/home.php/
        header('Location: admin/home.php');
        exit;
    }

    if ($user['role'] === 'client') { //if that role client, open the client/home.php/
        header('Location: client/home.php');
        exit;
    }
}

echo '<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="page">
        <h1>Login</h1>
        <p>Wrong username or password. <a href="index.php">Try again</a></p>
    </main>
</body>
</html>';

