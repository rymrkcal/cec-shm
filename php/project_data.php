<?php

function load_project($conn, $project_id) {
    if (!ctype_digit((string) $project_id)) {
        return null;
    }

    $stmt = mysqli_prepare($conn, 'SELECT project_id, project_name FROM projects WHERE project_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $project = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$project) {
        return null;
    }

    return $project;
}

function load_blocks($conn, $project_id) {
    $blocks = array();

    $block_stmt = mysqli_prepare($conn, 'SELECT block_id, block_name FROM blocks WHERE project_id = ? ORDER BY block_id');
    mysqli_stmt_bind_param($block_stmt, 'i', $project_id);
    mysqli_stmt_execute($block_stmt);
    $block_result = mysqli_stmt_get_result($block_stmt);

    $sensor_stmt = mysqli_prepare($conn, 'SELECT sensor_id, sensor_number, formula FROM sensors WHERE block_id = ? ORDER BY sensor_id');

    while ($block = mysqli_fetch_assoc($block_result)) {
        $block_id = (int) $block['block_id'];
        $block['sensors'] = array();

        mysqli_stmt_bind_param($sensor_stmt, 'i', $block_id);
        mysqli_stmt_execute($sensor_stmt);
        $sensor_result = mysqli_stmt_get_result($sensor_stmt);

        while ($sensor = mysqli_fetch_assoc($sensor_result)) {
            $block['sensors'][] = $sensor;
        }

        $blocks[] = $block;
    }

    mysqli_stmt_close($sensor_stmt);
    mysqli_stmt_close($block_stmt);

    return $blocks;
}

function insert_was_duplicate($stmt) {
    try {
        if (mysqli_stmt_execute($stmt)) {
            return false;
        }
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() === 1062) {
            return true;
        }

        throw $e;
    }

    return mysqli_stmt_errno($stmt) === 1062;
}

function project_name_taken($conn, $project_name, $except_id = 0) {
    $project_name = strtoupper(trim($project_name));
    $except_id = (int) $except_id;

    $stmt = mysqli_prepare($conn, 'SELECT project_id FROM projects WHERE UPPER(TRIM(project_name)) = ? AND project_id <> ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'si', $project_name, $except_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $taken = mysqli_fetch_assoc($result) ? true : false;
    mysqli_stmt_close($stmt);

    return $taken;
}

function block_name_taken($conn, $project_id, $block_name, $except_id = 0) {
    $block_name = strtoupper(trim($block_name));
    $project_id = (int) $project_id;
    $except_id = (int) $except_id;

    $stmt = mysqli_prepare($conn, 'SELECT block_id FROM blocks WHERE project_id = ? AND UPPER(TRIM(block_name)) = ? AND block_id <> ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'isi', $project_id, $block_name, $except_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $taken = mysqli_fetch_assoc($result) ? true : false;
    mysqli_stmt_close($stmt);

    return $taken;
}

function sensor_name_taken($conn, $block_id, $sensor_number, $except_id = 0) {
    $sensor_number = strtoupper(trim($sensor_number));
    $block_id = (int) $block_id;
    $except_id = (int) $except_id;

    $stmt = mysqli_prepare($conn, 'SELECT sensor_id FROM sensors WHERE block_id = ? AND UPPER(TRIM(sensor_number)) = ? AND sensor_id <> ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'isi', $block_id, $sensor_number, $except_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $taken = mysqli_fetch_assoc($result) ? true : false;
    mysqli_stmt_close($stmt);

    return $taken;
}

