<?php
require __DIR__ . '/../php/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

require __DIR__ . '/../config/database.php';

$project_id = $_POST['project_id'] ?? '';
$block_id = $_POST['block_id'] ?? '';

if (!ctype_digit((string) $project_id) || !ctype_digit((string) $block_id)) {
    header('Location: home.php');
    exit;
}

$project_id = (int) $project_id;
$block_id = (int) $block_id;

$check = mysqli_prepare($conn, 'SELECT block_id FROM blocks WHERE block_id = ? AND project_id = ?');
mysqli_stmt_bind_param($check, 'ii', $block_id, $project_id);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);
$block = mysqli_fetch_assoc($result);
mysqli_stmt_close($check);

if (!$block) {
    header('Location: project.php?id=' . $project_id);
    exit;
}

$blocks = mysqli_prepare($conn, 'DELETE FROM blocks WHERE block_id = ? AND project_id = ?');
mysqli_stmt_bind_param($blocks, 'ii', $block_id, $project_id);
mysqli_stmt_execute($blocks);
mysqli_stmt_close($blocks);

header('Location: project.php?id=' . $project_id);
exit;
