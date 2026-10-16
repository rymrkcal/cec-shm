<?php
require __DIR__ . '/../php/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

require __DIR__ . '/../config/database.php';

$project_id = $_POST['project_id'] ?? '';
$sensor_id = $_POST['sensor_id'] ?? '';

if (!ctype_digit((string) $project_id) || !ctype_digit((string) $sensor_id)) {
    header('Location: home.php');
    exit;
}

$project_id = (int) $project_id;
$sensor_id = (int) $sensor_id;

$check = mysqli_prepare($conn, 'SELECT sensors.sensor_id FROM sensors JOIN blocks ON blocks.block_id = sensors.block_id WHERE sensors.sensor_id = ? AND blocks.project_id = ?');
mysqli_stmt_bind_param($check, 'ii', $sensor_id, $project_id);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);
$sensor = mysqli_fetch_assoc($result);
mysqli_stmt_close($check);

if (!$sensor) {
    header('Location: project.php?id=' . $project_id);
    exit;
}

$sensors = mysqli_prepare($conn, 'DELETE FROM sensors WHERE sensor_id = ?');
mysqli_stmt_bind_param($sensors, 'i', $sensor_id);
mysqli_stmt_execute($sensors);
mysqli_stmt_close($sensors);

header('Location: project.php?id=' . $project_id);
exit;
