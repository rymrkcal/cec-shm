<?php
require __DIR__ . '/../php/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

require '../config/database.php';
require '../php/project_data.php';

$project_id = $_POST['project_id'] ?? '';
$block_name = strtoupper(trim($_POST['block_name'] ?? ''));

if (!ctype_digit((string) $project_id) || $block_name === '') { //if name is not exist, proceed to home.php
    header('Location: home.php');
    exit;
}

if (block_name_taken($conn, $project_id, $block_name)) {
    header('Location: project.php?id=' . $project_id . '&show=add-block&error=block&name=' . rawurlencode($block_name));
    exit;
}

$sql = 'INSERT INTO blocks (project_id, block_name) VALUES (?, ?)';
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'is', $project_id, $block_name);
$duplicate = insert_was_duplicate($stmt);
mysqli_stmt_close($stmt);

if ($duplicate) {
    header('Location: project.php?id=' . $project_id . '&show=add-block&error=block&name=' . rawurlencode($block_name));
    exit;
}

header('Location: project.php?id=' . $project_id);
exit;

