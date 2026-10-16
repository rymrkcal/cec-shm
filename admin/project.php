<?php
require __DIR__ . '/../php/auth.php';
require __DIR__ . '/../php/project_data.php';
require_once __DIR__ . '/../php/icons.php';
require __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['l3_form'])) {
    require_once __DIR__ . '/../php/calculations/3l/data.php';
    l3_save($conn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project']) && ctype_digit((string) ($_POST['project_id'] ?? ''))) {
    require_once __DIR__ . '/../php/calculations/3l/data.php';
    l3_commit_project($conn, (int) $_POST['project_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['atg_form']) || isset($_POST['save_project']) || isset($_POST['draft_only']))) {
    require_once __DIR__ . '/../php/calculations/atg/data.php';
    atg_save($conn);
}

$project_id = $_GET['id'] ?? '';
$show = $_GET['show'] ?? '';
$project = load_project($conn, $project_id);
$blocks = array();
$selected_block = null;
$selected_sensor = null;

if ($project) {
    $blocks = load_blocks($conn, $project['project_id']);

    $block_id = $_GET['block_id'] ?? '';
    if ($show === 'add-sensor' && ctype_digit((string) $block_id)) {
        foreach ($blocks as $block) {
            if ((int) $block['block_id'] === (int) $block_id) {
                $selected_block = $block;
            }
        }
    }

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
    <link rel="stylesheet" href="../css/style.css?v=12">
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
                    <span class="nav-icons">
                        <?php
                        $save_next = array('id' => (int) $project['project_id']);
                        if (ctype_digit((string) ($_GET['sensor_id'] ?? ''))) {
                            $save_next['sensor_id'] = (int) $_GET['sensor_id'];
                        }
                        if (ctype_digit((string) ($_GET['block_id'] ?? ''))) {
                            $save_next['block_id'] = (int) $_GET['block_id'];
                        }
                        $save_tab = $_GET['tab'] ?? '';
                        if (in_array($save_tab, array('parameter', 'details', 'measured', 'output', 'theoretical'), true)) {
                            $save_next['tab'] = $save_tab;
                        }
                        $save_part = $_GET['part'] ?? '';
                        if (in_array($save_part, array('modulus', 'shrinkage', 'creep'), true)) {
                            $save_next['part'] = $save_part;
                        }
                        $save_show = $_GET['show'] ?? '';
                        if (in_array($save_show, array('add-block', 'add-sensor'), true)) {
                            $save_next['show'] = $save_show;
                        }
                        ?>
                        <form class="save-project-form" method="post" action="project.php?id=<?php echo (int) $project['project_id']; ?>">
                            <input type="hidden" name="save_project" value="1">
                            <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
                            <input type="hidden" name="next" value="<?php echo htmlspecialchars('project.php?' . http_build_query($save_next)); ?>">
                            <button class="save-project" type="submit" aria-label="Save project"><?php echo icon_save(); ?></button>
                        </form>
                        <a class="add-block" href="project.php?id=<?php echo (int) $project['project_id']; ?>&show=add-block">+</a>
                        <form method="post" action="delete_project.php" onsubmit="return confirm('Delete this project, its blocks, and its sensors?');">
                            <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
                            <button class="trash" type="submit" aria-label="Delete project"><?php echo icon_trash(); ?></button>
                        </form>
                    </span>
                </div>
                <?php foreach ($blocks as $block) { ?>
                    <div class="nav-block" data-block-id="<?php echo (int) $block['block_id']; ?>">
                        <div class="nav-row">
                            <button type="button" class="block-toggle"><?php echo htmlspecialchars($block['block_name']); ?></button>
                            <span class="nav-icons">
                                <a class="add-icon" href="project.php?id=<?php echo (int) $project['project_id']; ?>&show=add-sensor&block_id=<?php echo (int) $block['block_id']; ?>" aria-label="Add sensor">+</a>
                                <form method="post" action="delete_block.php" onsubmit="return confirm('Delete this block and its sensors?');">
                                    <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
                                    <input type="hidden" name="block_id" value="<?php echo (int) $block['block_id']; ?>">
                                    <button class="trash" type="submit" aria-label="Delete block"><?php echo icon_trash(); ?></button>
                                </form>
                            </span>
                        </div>
                        <div class="nav-sensors">
                            <?php foreach ($block['sensors'] as $sensor) { ?>
                                <div class="sensor-row">
                                    <a class="sensor-link" href="project.php?id=<?php echo (int) $project['project_id']; ?>&sensor_id=<?php echo (int) $sensor['sensor_id']; ?>"><?php echo htmlspecialchars($sensor['sensor_number']); ?></a>
                                    <form method="post" action="delete_sensor.php" onsubmit="return confirm('Delete this sensor?');">
                                        <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
                                        <input type="hidden" name="sensor_id" value="<?php echo (int) $sensor['sensor_id']; ?>">
                                        <button class="trash" type="submit" aria-label="Delete sensor"><?php echo icon_trash(); ?></button>
                                    </form>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        </nav>
        <main class="admin-page">
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
                <?php if ($show === 'add-block') { ?>
                    <h2>Add block</h2>
                    <?php if (($_GET['error'] ?? '') === 'block') { ?>
                        <p class="form-error">This block name already exists in this project.</p>
                    <?php } ?>
                    <form method="post" action="create_block.php">
                        <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
                        <p>
                            <label for="block_name">Block name</label>
                            <input type="text" name="block_name" id="block_name" class="uppercase" value="<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>" required>
                        </p>
                        <button type="submit">Save</button>
                    </form>
                <?php } ?>

                <?php if ($selected_block) { ?>
                    <h2>Add sensor</h2>
                    <?php if (($_GET['error'] ?? '') === 'sensor') { ?>
                        <p class="form-error">This sensor name already exists in this block.</p>
                    <?php } ?>
                    <form method="post" action="create_sensor.php">
                        <input type="hidden" name="project_id" value="<?php echo (int) $project['project_id']; ?>">
                        <input type="hidden" name="block_id" value="<?php echo (int) $selected_block['block_id']; ?>">
                        <p>
                            <label for="sensor_number">Sensor number</label>
                            <input type="text" name="sensor_number" id="sensor_number" class="uppercase" value="<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>" required>
                        </p>
                        <p>
                            <label for="formula">Formula</label>
                            <select name="formula" id="formula" required>
                                <option value="ATG"<?php echo (($_GET['formula'] ?? '') === '3L') ? '' : ' selected'; ?>>ATG</option>
                                <option value="3L"<?php echo (($_GET['formula'] ?? '') === '3L') ? ' selected' : ''; ?>>3L</option>
                            </select>
                        </p>
                        <button type="submit">Save</button>
                    </form>
                <?php } ?>
            <?php } ?>
        </main>
    </div>
    <script src="../js/sidenav.js?v=1"></script>
</body>
</html>

