<?php
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'client')) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/config/database.php';
require __DIR__ . '/php/session_guard.php';
reject_stale_session($conn, 'index.php');
require __DIR__ . '/php/client_access.php';

$user_id = current_user_id($conn);
if ($user_id === 0) {
    header('Location: logout.php');
    exit;
}

function account_redirect($query) {
    header('Location: account.php?' . $query);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_username'])) {
    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        account_redirect('error=username');
    }

    if (strlen($username) > 10) {
        account_redirect('error=long&name=' . rawurlencode($username));
    }

    $check = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE username = ? AND user_id <> ? LIMIT 1');
    mysqli_stmt_bind_param($check, 'si', $username, $user_id);
    mysqli_stmt_execute($check);
    $result = mysqli_stmt_get_result($check);
    $taken = mysqli_fetch_assoc($result) ? true : false;
    mysqli_stmt_close($check);

    if ($taken) {
        account_redirect('error=taken&name=' . rawurlencode($username));
    }

    $update = mysqli_prepare($conn, 'UPDATE users SET username = ? WHERE user_id = ?');
    mysqli_stmt_bind_param($update, 'si', $username, $user_id);

    try {
        $saved = mysqli_stmt_execute($update);
    } catch (mysqli_sql_exception $e) {
        mysqli_stmt_close($update);
        if ((int) $e->getCode() === 1062) {
            account_redirect('error=taken&name=' . rawurlencode($username));
        }
        throw $e;
    }

    mysqli_stmt_close($update);

    if (!$saved) {
        account_redirect('error=save&name=' . rawurlencode($username));
    }

    $_SESSION['username'] = $username;
    account_redirect('saved=username');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        account_redirect('error=password');
    }

    if (strlen($new_password) < 8) {
        account_redirect('error=short');
    }

    if ($new_password !== $confirm_password) {
        account_redirect('error=match');
    }

    $lookup = mysqli_prepare($conn, 'SELECT password_hash FROM users WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($lookup, 'i', $user_id);
    mysqli_stmt_execute($lookup);
    $result = mysqli_stmt_get_result($lookup);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($lookup);

    if (!$user || !password_verify($current_password, $user['password_hash'])) {
        account_redirect('error=current');
    }

    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    ensure_session_version_column($conn);
    $update = mysqli_prepare($conn, 'UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE user_id = ?');
    mysqli_stmt_bind_param($update, 'si', $password_hash, $user_id);
    $saved = mysqli_stmt_execute($update);
    mysqli_stmt_close($update);

    if (!$saved) {
        account_redirect('error=password');
    }

    end_session();
    header('Location: index.php?notice=password');
    exit;
}

$username_value = $_SESSION['username'] ?? '';
if (isset($_GET['name'])) {
    $username_value = $_GET['name'];
}

$nav_root = '';
$back_href = $_SESSION['role'] === 'admin' ? 'admin/home.php' : 'client/home.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Account</title>
    <link rel="stylesheet" href="css/style.css?v=10">
</head>
<body>
    <?php require __DIR__ . '/php/topbar.php'; ?>
    <main class="admin-page">
        <h1>My Account</h1>

        <?php if (($_GET['saved'] ?? '') === 'username') { ?>
            <p class="form-success">Username updated.</p>
        <?php } ?>
        <?php if (($_GET['error'] ?? '') === 'username') { ?>
            <p class="form-error">Enter a username.</p>
        <?php } ?>
        <?php if (($_GET['error'] ?? '') === 'long') { ?>
            <p class="form-error">Username must be 10 characters or fewer.</p>
        <?php } ?>
        <?php if (($_GET['error'] ?? '') === 'taken') { ?>
            <p class="form-error">That username is already used.</p>
        <?php } ?>
        <?php if (($_GET['error'] ?? '') === 'save') { ?>
            <p class="form-error">Could not save the username.</p>
        <?php } ?>
        <?php if (($_GET['error'] ?? '') === 'password') { ?>
            <p class="form-error">Enter the current password, a new password, and the confirmation.</p>
        <?php } ?>
        <?php if (($_GET['error'] ?? '') === 'short') { ?>
            <p class="form-error">New password must be at least 8 characters.</p>
        <?php } ?>
        <?php if (($_GET['error'] ?? '') === 'match') { ?>
            <p class="form-error">New password and confirmation do not match.</p>
        <?php } ?>
        <?php if (($_GET['error'] ?? '') === 'current') { ?>
            <p class="form-error">Current password is incorrect.</p>
        <?php } ?>

        <h2>Username</h2>
        <form method="post" action="account.php">
            <input type="hidden" name="save_username" value="1">
            <p>
                <label for="username">Username</label>
                <input type="text" name="username" id="username" maxlength="50" value="<?php echo htmlspecialchars($username_value); ?>" required>
            </p>
            <button type="submit">Save username</button>
        </form>

        <h2>Password</h2>
        <form method="post" action="account.php">
            <input type="hidden" name="save_password" value="1">
            <p>
                <label for="current_password">Current password</label>
                <input type="password" name="current_password" id="current_password" required>
            </p>
            <p>
                <label for="new_password">New password</label>
                <input type="password" name="new_password" id="new_password" required>
            </p>
            <p>
                <label for="confirm_password">Confirm new password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </p>
            <button type="submit">Save password</button>
        </form>
    </main>
</body>
</html>

