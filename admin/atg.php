<?php
require __DIR__ . '/../php/auth.php';

require '../config/database.php';

$sensor_id = $_GET['sensor_id'] ?? '';

if (!ctype_digit((string) $sensor_id)) {
    header('Location: home.php');
    exit;
}

$sql = 'SELECT blocks.project_id
    FROM sensors
    JOIN blocks ON blocks.block_id = sensors.block_id
    WHERE sensors.sensor_id = ?';
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $sensor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sensor = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$sensor) {
    header('Location: home.php');
    exit;
}

header('Location: project.php?id=' . $sensor['project_id'] . '&sensor_id=' . $sensor_id);
exit;

