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
require __DIR__ . '/../php/project_data.php';

if (!ensure_client_projects($conn)) {
    die('Could not prepare project access.');
}

$user_id = current_user_id($conn);
$project_id = $_GET['id'] ?? '';
$project = null;
$blocks = array();
$selected_sensor = null;
$calc_readonly = true;

if (client_can_open_project($conn, $user_id, $project_id)) {
    $project = load_project($conn, $project_id);
}

if ($project) {
    $blocks = load_blocks($conn, $project['project_id']);
    $sensor_id = $_GET['sensor_id'] ?? '';
    if (ctype_digit((string) $sensor_id)) {
        foreach ($blocks as $block) {
            foreach ($block['sensors'] as $sensor) {
                if ((int) $sensor['sensor_id'] === (int) $sensor_id) {
                    $selected_sensor = $sensor;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Project</title>
    <link rel="stylesheet" href="../css/style.css?v=14">
</head>
<body class="admin">
    <?php
    $nav_root = '../';
    $back_href = 'home.php';
    require __DIR__ . '/../php/topbar.php';
    ?>
    <div class="admin-layout">
        <nav class="sidenav">
            <?php if ($project) { ?>
                <div class="nav-row">
                    <span class="sidenav-title"><?php echo htmlspecialchars($project['project_name']); ?></span>
                </div>
                <?php foreach ($blocks as $block) { ?>
                    <div class="nav-block" data-block-id="<?php echo (int) $block['block_id']; ?>">
                        <div class="nav-row">
                            <button type="button" class="block-toggle"><?php echo htmlspecialchars($block['block_name']); ?></button>
                        </div>
                        <div class="nav-sensors">
                            <?php foreach ($block['sensors'] as $sensor) {
                                $sensor_href = 'project.php?id=' . (int) $project['project_id'] . '&sensor_id=' . (int) $sensor['sensor_id'];
                                $sensor_class = 'sensor-link';
                                if ($selected_sensor && (int) $selected_sensor['sensor_id'] === (int) $sensor['sensor_id']) {
                                    $sensor_class .= ' active';
                                }
                                ?>
                                <div class="sensor-row">
                                    <a class="<?php echo $sensor_class; ?>" href="<?php echo $sensor_href; ?>"><?php echo htmlspecialchars($sensor['sensor_number']); ?></a>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        </nav>
        <main class="admin-page view-calc">
            <?php if (!$project) { ?>
                <h1>Project not found</h1>
            <?php } elseif ($selected_sensor && $selected_sensor['formula'] === 'ATG') { ?>
                <?php require __DIR__ . '/../php/calculations/atg/page.php'; ?>
            <?php } elseif ($selected_sensor && $selected_sensor['formula'] === '3L') { ?>
                <?php require __DIR__ . '/../php/calculations/3l/page.php'; ?>
            <?php } elseif ($selected_sensor) { ?>
                <h1><?php echo htmlspecialchars($selected_sensor['sensor_number']); ?></h1>
                <p>Sensor: <?php echo htmlspecialchars($selected_sensor['sensor_number']); ?></p>
            <?php } else { ?>
                <h1><?php echo htmlspecialchars($project['project_name']); ?></h1>
                <p>Select a sensor to view the calculation.</p>
            <?php } ?>
        </main>
    </div>
    <script src="../js/sidenav.js?v=1"></script>
</body>
</html>

