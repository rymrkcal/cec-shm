<?php
session_start();

require __DIR__ . '/php/session_guard.php';
end_session();

header('Location: index.php');
exit;

