<?php
require __DIR__ . '/../php/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

require '../config/database.php';
require '../php/project_data.php';

$project_name = strtoupper(trim($_POST['project_name'] ?? ''));

if ($project_name === '') {
    header('Location: home.php?show=create');
    exit;
}

if (project_name_taken($conn, $project_name)) {
    header('Location: home.php?show=create&error=project&name=' . rawurlencode($project_name));
    exit;
}

$sql = 'INSERT INTO projects (project_name) VALUES (?)';
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $project_name);
$duplicate = insert_was_duplicate($stmt);
mysqli_stmt_close($stmt);

if ($duplicate) {
    header('Location: home.php?show=create&error=project&name=' . rawurlencode($project_name));
    exit;
}

header('Location: home.php');
exit;

