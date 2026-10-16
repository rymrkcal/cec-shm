<?php

function ensure_client_projects($conn) {
    $sql = 'CREATE TABLE IF NOT EXISTS client_projects (
        user_id INT NOT NULL,
        project_id INT NOT NULL,
        PRIMARY KEY (user_id, project_id),
        CONSTRAINT fk_client_projects_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
        CONSTRAINT fk_client_projects_project FOREIGN KEY (project_id) REFERENCES projects (project_id) ON DELETE CASCADE
    ) ENGINE=InnoDB';

    return mysqli_query($conn, $sql) ? true : false;
}

// Return the logged-in user id.
function current_user_id($conn) {
    if (isset($_SESSION['user_id']) && ctype_digit((string) $_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    $username = $_SESSION['username'] ?? '';
    if ($username === '') {
        return 0;
    }

    $stmt = mysqli_prepare($conn, 'SELECT user_id FROM users WHERE username = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        return 0;
    }

    $_SESSION['user_id'] = (int) $row['user_id'];

    return (int) $row['user_id'];
}

// Read every user whose role is client.
function load_clients($conn) {
    $clients = array();
    $result = mysqli_query($conn, "SELECT user_id, username FROM users WHERE role = 'client' ORDER BY username");

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $clients[] = $row;
        }
    }

    return $clients;
}

function client_user($conn, $user_id) {
    $user_id = (int) $user_id;
    $stmt = mysqli_prepare($conn, "SELECT user_id, username FROM users WHERE user_id = ? AND role = 'client' LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        return null;
    }

    return $row;
}

function load_all_projects($conn) {
    $projects = array();
    $result = mysqli_query($conn, 'SELECT project_id, project_name FROM projects ORDER BY project_name');

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $projects[] = $row;
        }
    }

    return $projects;
}

function client_project_ids($conn, $user_id) {
    $user_id = (int) $user_id;
    $ids = array();
    $stmt = mysqli_prepare($conn, 'SELECT project_id FROM client_projects WHERE user_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $ids[] = (int) $row['project_id'];
    }

    mysqli_stmt_close($stmt);

    return $ids;
}

// True when this client is allowed to open this project.
function client_can_open_project($conn, $user_id, $project_id) {
    $user_id = (int) $user_id;
    $project_id = (int) $project_id;
    if ($user_id === 0 || $project_id === 0) {
        return false;
    }

    $stmt = mysqli_prepare($conn, 'SELECT 1 FROM client_projects WHERE user_id = ? AND project_id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ? true : false;
}

// Return the projects assigned to this client.
function projects_for_client($conn, $user_id) {
    $user_id = (int) $user_id;
    $projects = array();
    $stmt = mysqli_prepare($conn, 'SELECT projects.project_id, projects.project_name
        FROM projects
        INNER JOIN client_projects ON client_projects.project_id = projects.project_id
        WHERE client_projects.user_id = ?
        ORDER BY projects.project_name');
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $projects[] = $row;
    }

    mysqli_stmt_close($stmt);

    return $projects;
}

function valid_project_id_list($conn, $posted) {
    if (!is_array($posted)) {
        return null;
    }

    $ids = array();
    foreach ($posted as $id) {
        if (!ctype_digit((string) $id)) {
            return null;
        }
        $ids[(int) $id] = (int) $id;
    }

    $ids = array_values($ids);
    if (count($ids) === 0) {
        return array();
    }

    $found = 0;
    $stmt = mysqli_prepare($conn, 'SELECT project_id FROM projects WHERE project_id = ?');
    foreach ($ids as $project_id) {
        mysqli_stmt_bind_param($stmt, 'i', $project_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_fetch_assoc($result)) {
            $found++;
        }
    }
    mysqli_stmt_close($stmt);

    if ($found !== count($ids)) {
        return null;
    }

    return $ids;
}

function save_client_projects($conn, $user_id, $project_ids) {
    $user_id = (int) $user_id;
    $delete = mysqli_prepare($conn, 'DELETE FROM client_projects WHERE user_id = ?');
    mysqli_stmt_bind_param($delete, 'i', $user_id);
    mysqli_stmt_execute($delete);
    mysqli_stmt_close($delete);

    if (count($project_ids) === 0) {
        return;
    }

    $insert = mysqli_prepare($conn, 'INSERT INTO client_projects (user_id, project_id) VALUES (?, ?)');
    foreach ($project_ids as $project_id) {
        $project_id = (int) $project_id;
        mysqli_stmt_bind_param($insert, 'ii', $user_id, $project_id);
        mysqli_stmt_execute($insert);
    }
    mysqli_stmt_close($insert);
}

// Remove every client's access to this project.
function clear_client_projects($conn, $project_id) {
    ensure_client_projects($conn);
    $project_id = (int) $project_id;
    $stmt = mysqli_prepare($conn, 'DELETE FROM client_projects WHERE project_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $project_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

