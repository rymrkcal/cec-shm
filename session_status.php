<?php
session_start();

require __DIR__ . '/config/database.php';
require __DIR__ . '/php/session_guard.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

if (!isset($_SESSION['role']) || !session_is_current($conn)) {
    $notice = isset($_SESSION['session_version']) ? 'signedout' : 'relogin';
    end_session();
    http_response_code(401);
    echo json_encode(array('ok' => false, 'notice' => $notice));
    exit;
}

session_write_close();
echo '{"ok":true}';

