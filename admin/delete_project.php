<?php
require __DIR__ . '/../php/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

require __DIR__ . '/../config/database.php';

$project_id = $_POST['project_id'] ?? '';

if (!ctype_digit((string) $project_id)) {
    header('Location: home.php');
    exit;
}

$project_id = (int) $project_id;

$check = mysqli_prepare($conn, 'SELECT project_id FROM projects WHERE project_id = ?');
mysqli_stmt_bind_param($check, 'i', $project_id);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);
$project = mysqli_fetch_assoc($result);
mysqli_stmt_close($check);

if (!$project) {
    header('Location: home.php');
    exit;
}

$projects = mysqli_prepare($conn, 'DELETE FROM projects WHERE project_id = ?');
mysqli_stmt_bind_param($projects, 'i', $project_id);
mysqli_stmt_execute($projects);
mysqli_stmt_close($projects);

header('Location: home.php');
exit;
