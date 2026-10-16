
<?php
session_start(); //1 start here 

if (isset($_SESSION['role'])) { 
    require __DIR__ . '/config/database.php'; //2 if already sign in check database
    require __DIR__ . '/php/session_guard.php'; //3 check session if valid
    reject_stale_session($conn, 'index.php');
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { //if the role id admin, go to admin.php
    header('Location: admin/home.php');
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'client') { //if client. go to the client.php
    header('Location: client/home.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="page">
        <h1>Login</h1>
        <?php if (($_GET['notice'] ?? '') === 'password') { ?>
            <p class="form-success">Password updated. Log in again with the new password.</p>
        <?php } ?>
        <?php if (($_GET['notice'] ?? '') === 'signedout') { ?>
            <p class="form-success">You were signed out because the password changed. Log in again.</p>
        <?php } ?>
        <?php if (($_GET['notice'] ?? '') === 'relogin') { ?>
            <p class="form-success">Please log in again.</p>
        <?php } ?>
        <form method="post" action="process_login.php"> 
            <p>
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>
            </p>
            <p>
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </p>
            <button type="submit">Login</button>
        </form>
    </main>
</body>
</html>

