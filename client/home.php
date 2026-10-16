<?php
session_start();

if (!isset($_SESSION['role'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SESSION['role'] !== 'client') {
    header('Location: ../admin/home.php');
    exit;
}

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../php/session_guard.php';
reject_stale_session($conn, '../index.php');
require __DIR__ . '/../php/client_access.php';

if (!ensure_client_projects($conn)) {
    die('Could not prepare project access.');
}

$user_id = current_user_id($conn);
$projects = $user_id === 0 ? array() : projects_for_client($conn, $user_id);
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client</title>
    <link rel="stylesheet" href="../css/style.css?v=11">
</head>
<body>
    <?php
    $nav_root = '../';
    $back_href = '';
    require __DIR__ . '/../php/topbar.php';
    ?>
    <main class="admin-page">
        <h1>Projects</h1>
        <?php if (count($projects) === 0) { ?>
            <p>No projects yet.</p>
        <?php } else { ?>
            <ul class="name-list">
                <?php foreach ($projects as $project) { ?>
                    <li><a href="project.php?id=<?php echo (int) $project['project_id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></a></li>
                <?php } ?>
            </ul>
        <?php } ?>
    </main>
</body>
</html>

