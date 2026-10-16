<?php

function ensure_session_version_column($conn) {
    static $done = false;
    if ($done) {
        return;
    }

    $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'session_version'");
    if ($result && mysqli_num_rows($result) > 0) {
        $done = true;
        return;
    }

    mysqli_query($conn, 'ALTER TABLE users ADD COLUMN session_version INT NOT NULL DEFAULT 1');
    $done = true;
}

function session_user_id($conn) {
    if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0) {
        return (int) $_SESSION['user_id'];
    }

    $username = $_SESSION['username'] ?? '';
    if ($username === '') {
        return 0;
    }

    $stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE username = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        return 0;
    }

    return (int) $row['user_id'];
}

function user_session_version($conn, $user_id) {
    ensure_session_version_column($conn);
    $user_id = (int) $user_id;
    $stmt = mysqli_prepare($conn, 'SELECT session_version FROM users WHERE user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        return null;
    }

    return (int) $row['session_version'];
}

function session_is_current($conn) {
    if (!isset($_SESSION['role']) || !isset($_SESSION['session_version'])) {
        return false;
    }

    $user_id = session_user_id($conn);
    if ($user_id === 0) {
        return false;
    }

    $version = user_session_version($conn, $user_id);
    if ($version === null) {
        return false;
    }

    return (int) $_SESSION['session_version'] === $version;
}

function end_session() {
    $_SESSION = array();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function reject_stale_session($conn, $login_url) {
    if (!isset($_SESSION['role'])) {
        return;
    }

    if (session_is_current($conn)) {
        return;
    }

    $notice = isset($_SESSION['session_version']) ? 'signedout' : 'relogin';
    end_session();
    header('Location: ' . $login_url . '?notice=' . $notice);
    exit;
}

