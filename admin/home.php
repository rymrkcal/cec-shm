<?php
require __DIR__ . '/../php/auth.php';

$show = $_GET['show'] ?? '';
$projects = null;

if ($show === 'open') {
    require '../config/database.php';
    $projects = mysqli_query($conn, 'SELECT project_id, project_name FROM projects ORDER BY project_id');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin</title>
    <link rel="stylesheet" href="../css/style.css?v=10">
</head>
<body>
    <?php
    $nav_root = '../';
    $back_href = '';
    require __DIR__ . '/../php/topbar.php';
    ?>
    <main class="admin-page">
        <h1>CEC SHM CALCULATOR</h1>
        <div class="actions">
            <a class="open-button" href="home.php?show=create">Create</a>
            <a class="open-button" href="home.php?show=open">Open</a>
        </div>

        <?php if ($show === 'create') { ?>
            <h2>Create project</h2>
            <?php if (($_GET['error'] ?? '') === 'project') { ?>
                <p class="form-error">This project name already exists.</p>
            <?php } ?>
            <form method="post" action="create_project.php">
                <p>
                    <label for="project_name">Project name</label>
                    <input type="text" name="project_name" id="project_name" class="uppercase" value="<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>" required>
                </p>
                <button type="submit">Create</button>
            </form>
        <?php } ?>

        <?php if ($show === 'open') { ?>
            <h2>Open project</h2>
            <?php if (mysqli_num_rows($projects) === 0) { ?>
                <p>No projects yet.</p>
            <?php } else { ?>
                <form method="get" action="project.php">
                    <p>
                        <label for="id">Choose a project</label>
                        <select name="id" id="id" required>
                            <?php while ($project = mysqli_fetch_assoc($projects)) { ?>
                                <option value="<?php echo (int) $project['project_id']; ?>"><?php echo htmlspecialchars($project['project_name']); ?></option>
                            <?php } ?>
                        </select>
                    </p>
                    <button type="submit">Open project</button>
                </form>
            <?php } ?>
        <?php } ?>
    </main>
</body>
</html>

