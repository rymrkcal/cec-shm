<?php
require __DIR__ . '/../php/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../php/client_access.php';

if (!ensure_client_projects($conn)) {
    die('Could not prepare project access.');
}

$clients = load_clients($conn);
$client = count($clients) === 1 ? $clients[0] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$client) {
        header('Location: clients.php?error=client');
        exit;
    }

    $project_ids = valid_project_id_list($conn, $_POST['project_ids'] ?? array());
    if ($project_ids === null) {
        header('Location: clients.php?error=projects');
        exit;
    }

    save_client_projects($conn, (int) $client['user_id'], $project_ids);
    header('Location: clients.php?saved=1');
    exit;
}

$projects = load_all_projects($conn);
$assigned = array();
if ($client) {
    $assigned = client_project_ids($conn, (int) $client['user_id']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Management</title>
    <link rel="stylesheet" href="../css/style.css?v=10">
</head>
<body>
    <?php
    $nav_root = '../';
    $back_href = 'home.php';
    require __DIR__ . '/../php/topbar.php';
    ?>
    <main class="admin-page">
        <h1>Project List</h1>

        <?php if (($_GET['saved'] ?? '') === '1') { ?>
            <p class="form-success">Project access saved.</p>
        <?php } ?>
        <?php if (($_GET['error'] ?? '') === 'client') { ?>
            <p class="form-error">No client account is available.</p>
        <?php } ?>
        <?php if (($_GET['error'] ?? '') === 'projects') { ?>
            <p class="form-error">One or more projects could not be saved.</p>
        <?php } ?>

        <?php if (!$client) { ?>
            <p>No clients yet.</p>
        <?php } elseif (count($projects) === 0) { ?>
            <p>No projects yet.</p>
        <?php } else { ?>
            <form method="post" action="clients.php">
                <ul class="check-list">
                    <?php foreach ($projects as $project) { ?>
                        <li>
                            <label>
                                <input type="checkbox" name="project_ids[]" value="<?php echo (int) $project['project_id']; ?>"<?php echo in_array((int) $project['project_id'], $assigned, true) ? ' checked' : ''; ?>>
                                <?php echo htmlspecialchars($project['project_name']); ?>
                            </label>
                        </li>
                    <?php } ?>
                </ul>
                <button type="submit">Save</button>
            </form>
        <?php } ?>
    </main>
</body>
</html>

