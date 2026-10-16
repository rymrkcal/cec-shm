<?php
require __DIR__ . '/../php/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

require '../config/database.php';
require '../php/project_data.php';

$project_id = $_POST['project_id'] ?? '';
$block_id = $_POST['block_id'] ?? '';
$sensor_number = strtoupper(trim($_POST['sensor_number'] ?? ''));
$formula = $_POST['formula'] ?? '';

if (!ctype_digit((string) $project_id) || !ctype_digit((string) $block_id) || $sensor_number === '') {
    header('Location: home.php');
    exit;
}

if ($formula !== 'ATG' && $formula !== '3L') {
    header('Location: project.php?id=' . $project_id . '&show=add-sensor&block_id=' . $block_id);
    exit;
}

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

if (sensor_name_taken($conn, $block_id, $sensor_number)) {
    header('Location: project.php?id=' . $project_id . '&show=add-sensor&block_id=' . $block_id . '&error=sensor&name=' . rawurlencode($sensor_number) . '&formula=' . rawurlencode($formula));
    exit;
}

$sql = 'INSERT INTO sensors (block_id, sensor_number, formula) VALUES (?, ?, ?)';
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iss', $block_id, $sensor_number, $formula);
$duplicate = insert_was_duplicate($stmt);
$sensor_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

if ($duplicate) {
    header('Location: project.php?id=' . $project_id . '&show=add-sensor&block_id=' . $block_id . '&error=sensor&name=' . rawurlencode($sensor_number) . '&formula=' . rawurlencode($formula));
    exit;
}

header('Location: project.php?id=' . $project_id . '&sensor_id=' . $sensor_id);
exit;

