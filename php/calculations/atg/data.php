<?php
require_once __DIR__ . '/common.php';
// ATG load, draft, and save.

function atg_max_row($conn, $sensor_id, $table) {
    if ($table === 'history') {
        $sql = 'SELECT MAX(row_no) AS max_row FROM atg_stress_history WHERE sensor_id = ?';
    } else {
        $sql = 'SELECT MAX(row_no) AS max_row FROM atg_readings WHERE sensor_id = ?';
    }
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $sensor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    $max = (int) ($row['max_row'] ?? 0);
    if ($max < 3) {
        return 3;
    }
    return $max;
}

function atg_load_params($conn, $sensor_id) {
    $params = atg_default_params();
    $stmt = mysqli_prepare($conn, 'SELECT concrete_grade, sfc, pour_date, rh, ac, ar, u_value, load_value, serial_no, ks, ktt, kts, kst, temp_fbg_1, temp_fbg_2, base_floor, mid_floor, roof_floor, creep_t0, ts_days, h0_value FROM atg_parameters WHERE sensor_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $sensor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        return $params;
    }

    $params['concrete_grade'] = atg_stored($row['concrete_grade']);
    $params['sfc'] = atg_stored($row['sfc']);
    $params['pour_date'] = atg_stored($row['pour_date']);
    $params['rh'] = atg_stored($row['rh']);
    $params['ac'] = atg_stored($row['ac']);
    $params['ar'] = atg_stored($row['ar']);
    $params['u'] = atg_stored($row['u_value']);
    $params['load_value'] = atg_stored($row['load_value']);
    $params['serial_no'] = atg_stored($row['serial_no']);
    $params['ks'] = atg_stored($row['ks']);
    $params['ktt'] = atg_stored($row['ktt']);
    $params['kts'] = atg_stored($row['kts']);
    $params['kst'] = atg_stored($row['kst']);
    $params['temp_fbg_1'] = atg_stored($row['temp_fbg_1']);
    $params['temp_fbg_2'] = atg_stored($row['temp_fbg_2']);
    $params['base_floor'] = atg_stored($row['base_floor']);
    $params['mid_floor'] = atg_stored($row['mid_floor']);
    $params['roof_floor'] = atg_stored($row['roof_floor']);
    $params['creep_t0'] = '28';
    $params['ts'] = '0';
    $params['h0'] = '100';
    return $params;
}

function atg_load_readings($conn, $sensor_id) {
    $by_no = array();
    $stmt = mysqli_prepare($conn, 'SELECT row_no, item_name, reading_date, bottom_strain, bottom_temp, top_strain, top_temp FROM atg_readings WHERE sensor_id = ? ORDER BY row_no');
    mysqli_stmt_bind_param($stmt, 'i', $sensor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['item_name'] = atg_stored($row['item_name']);
        $row['reading_date'] = atg_stored($row['reading_date']);
        $row['bottom_strain'] = atg_stored($row['bottom_strain']);
        $row['bottom_temp'] = atg_stored($row['bottom_temp']);
        $row['top_strain'] = atg_stored($row['top_strain']);
        $row['top_temp'] = atg_stored($row['top_temp']);
        $row['stress_date'] = '';
        $by_no[(int) $row['row_no']] = $row;
    }
    mysqli_stmt_close($stmt);

    $readings = array();
    $count = atg_max_row($conn, $sensor_id, 'readings');
    for ($row_no = 1; $row_no <= $count; $row_no++) {
        if (isset($by_no[$row_no])) {
            $readings[] = $by_no[$row_no];
        } else {
            $readings[] = atg_blank_reading($row_no);
        }
    }
    return $readings;
}

function atg_load_history($conn, $sensor_id) {
    $by_no = array();
    $stmt = mysqli_prepare($conn, 'SELECT row_no, history_date FROM atg_stress_history WHERE sensor_id = ? ORDER BY row_no');
    mysqli_stmt_bind_param($stmt, 'i', $sensor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['history_date'] = atg_stored($row['history_date']);
        $by_no[(int) $row['row_no']] = $row;
    }
    mysqli_stmt_close($stmt);

    $history = array();
    $count = atg_max_row($conn, $sensor_id, 'history');
    for ($row_no = 1; $row_no <= $count; $row_no++) {
        if (isset($by_no[$row_no])) {
            $history[] = $by_no[$row_no];
        } else {
            $history[] = array(
                'row_no' => $row_no,
                'history_date' => '',
            );
        }
    }
    return $history;
}

function atg_sensor_ok($conn, $project_id, $sensor_id) {
    $stmt = mysqli_prepare($conn, 'SELECT sensors.sensor_id FROM sensors JOIN blocks ON blocks.block_id = sensors.block_id WHERE sensors.sensor_id = ? AND blocks.project_id = ? AND sensors.formula = "ATG"');
    mysqli_stmt_bind_param($stmt, 'ii', $sensor_id, $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? true : false;
}

function atg_draft_section($project_id, $sensor_id, $section) {
    $project_id = (int) $project_id;
    $sensor_id = (int) $sensor_id;
    if (!isset($_SESSION['project_draft'][$project_id][$sensor_id][$section]) || !is_array($_SESSION['project_draft'][$project_id][$sensor_id][$section])) {
        return null;
    }
    return $_SESSION['project_draft'][$project_id][$sensor_id][$section];
}

function atg_draft_put($project_id, $sensor_id, $section, $data) {
    $_SESSION['project_draft'][(int) $project_id][(int) $sensor_id][$section] = $data;
}

function atg_pad_rows($rows, $kind) {
    $by_no = array();
    $max = 0;
    foreach ($rows as $row) {
        $row_no = (int) ($row['row_no'] ?? 0);
        if ($row_no < 1) {
            continue;
        }
        $by_no[$row_no] = $row;
        if ($row_no > $max) {
            $max = $row_no;
        }
    }
    if ($max < 3) {
        $max = 3;
    }
    $padded = array();
    for ($row_no = 1; $row_no <= $max; $row_no++) {
        if (isset($by_no[$row_no])) {
            $padded[] = $by_no[$row_no];
        } elseif ($kind === 'history') {
            $padded[] = array(
                'row_no' => $row_no,
                'history_date' => '',
            );
        } else {
            $padded[] = atg_blank_reading($row_no);
        }
    }
    return $padded;
}

function atg_posted_count($prefix) {
    $count = 0;
    $pattern = '/^' . preg_quote($prefix, '/') . '_(\d+)$/';
    foreach ($_POST as $key => $value) {
        if (preg_match($pattern, (string) $key, $match)) {
            $count = max($count, (int) $match[1]);
        }
    }
    return $count;
}

function atg_renumber_rows($rows, $drop) {
    $kept = array();
    foreach ($rows as $row) {
        if ((int) $row['row_no'] === $drop) {
            continue;
        }
        $kept[] = $row;
    }
    $numbered = array();
    $row_no = 1;
    foreach ($kept as $row) {
        $row['row_no'] = $row_no;
        $numbered[] = $row;
        $row_no++;
    }
    return $numbered;
}

function atg_params_from_post() {
    $params = atg_default_params();
    $params['concrete_grade'] = atg_clean($_POST['concrete_grade'] ?? '', 20);
    $params['sfc'] = atg_clean($_POST['sfc'] ?? '', 40);
    $params['pour_date'] = atg_clean($_POST['pour_date'] ?? '', 40);
    $params['rh'] = atg_clean($_POST['rh'] ?? '', 40);
    $params['ac'] = atg_clean($_POST['ac'] ?? '', 40);
    $params['ar'] = atg_clean($_POST['ar'] ?? '', 40);
    $params['u'] = atg_clean($_POST['u'] ?? '', 40);
    $params['load_value'] = atg_clean($_POST['load_value'] ?? '', 40);
    $params['serial_no'] = atg_clean($_POST['serial_no'] ?? '', 50);
    $params['ks'] = atg_clean($_POST['ks'] ?? '', 40);
    $params['ktt'] = atg_clean($_POST['ktt'] ?? '', 40);
    $params['kts'] = atg_clean($_POST['kts'] ?? '', 40);
    $params['temp_fbg_1'] = atg_clean($_POST['temp_fbg_1'] ?? '', 40);
    $params['temp_fbg_2'] = atg_clean($_POST['temp_fbg_2'] ?? '', 40);
    $params['base_floor'] = atg_clean($_POST['base_floor'] ?? '', 20);
    $params['mid_floor'] = atg_clean($_POST['mid_floor'] ?? '', 20);
    $params['roof_floor'] = atg_clean($_POST['roof_floor'] ?? '', 20);
    $params['creep_t0'] = '28';
    $params['ts'] = '0';
    $params['h0'] = '100';
    return $params;
}

function atg_details_from_post() {
    $count = atg_posted_count('item_name');
    $rows = array();
    for ($row_no = 1; $row_no <= $count; $row_no++) {
        $rows[] = array(
            'row_no' => $row_no,
            'item_name' => atg_clean($_POST['item_name_' . $row_no] ?? '', 100),
            'reading_date' => atg_store_date(atg_clean($_POST['reading_date_' . $row_no] ?? '', 40)),
            'bottom_strain' => atg_clean($_POST['bottom_strain_' . $row_no] ?? '', 40),
            'bottom_temp' => atg_clean($_POST['bottom_temp_' . $row_no] ?? '', 40),
            'top_strain' => atg_clean($_POST['top_strain_' . $row_no] ?? '', 40),
            'top_temp' => atg_clean($_POST['top_temp_' . $row_no] ?? '', 40),
            'stress_date' => '',
        );
    }
    $delete_row = $_POST['delete_row'] ?? '';
    if (ctype_digit((string) $delete_row) && (int) $delete_row >= 1) {
        $rows = atg_renumber_rows($rows, (int) $delete_row);
    } elseif (isset($_POST['add_row'])) {
        $rows[] = atg_blank_reading(count($rows) + 1);
    }
    return $rows;
}

function atg_history_from_post() {
    $count = atg_posted_count('stress_date');
    $rows = array();
    for ($row_no = 1; $row_no <= $count; $row_no++) {
        $rows[] = array(
            'row_no' => $row_no,
            'history_date' => atg_store_date(atg_clean($_POST['stress_date_' . $row_no] ?? '', 40)),
        );
    }
    $delete_row = $_POST['delete_row'] ?? '';
    if (ctype_digit((string) $delete_row) && (int) $delete_row >= 1) {
        $rows = atg_renumber_rows($rows, (int) $delete_row);
    } elseif (isset($_POST['add_row'])) {
        $rows[] = array(
            'row_no' => count($rows) + 1,
            'history_date' => '',
        );
    }
    return $rows;
}

function atg_store_posted_form($project_id, $sensor_id, $form) {
    if ($form === 'parameter') {
        atg_draft_put($project_id, $sensor_id, 'parameter', atg_params_from_post());
    } elseif ($form === 'details') {
        atg_draft_put($project_id, $sensor_id, 'details', atg_details_from_post());
    } elseif ($form === 'modulus') {
        atg_draft_put($project_id, $sensor_id, 'modulus', atg_history_from_post());
    }
}

function atg_safe_next($next, $project_id) {
    $next = str_replace(array("\r", "\n"), '', (string) $next);
    if ($next === 'home.php') {
        return $next;
    }
    $pattern = '/^project\.php\?id=' . (int) $project_id . '(?:&[A-Za-z_][A-Za-z0-9_]*=[A-Za-z0-9_.+\-]*)*$/';
    if (preg_match($pattern, $next)) {
        return $next;
    }
    return 'project.php?id=' . (int) $project_id;
}

function atg_section_url($project_id, $sensor_id, $form) {
    $url = 'project.php?id=' . (int) $project_id . '&sensor_id=' . (int) $sensor_id;
    if ($form === 'details') {
        return $url . '&tab=details';
    }
    if ($form === 'modulus') {
        return $url . '&tab=theoretical&part=modulus';
    }
    return $url . '&tab=parameter';
}

function atg_write_params($conn, $sensor_id, $params) {
    $params = array_merge(atg_default_params(), $params);
    $grade = atg_db_number($params['concrete_grade'], true);
    $sfc = atg_db_number($params['sfc'], false);
    $pour_date = atg_clean($params['pour_date'], 40);
    $rh = atg_db_number($params['rh'], true);
    $ac = atg_db_number($params['ac'], false);
    $ar = atg_db_number($params['ar'], false);
    $u_value = atg_db_number($params['u'], true);
    $load_value = atg_db_number($params['load_value'], false);
    $serial_no = atg_clean($params['serial_no'], 50);
    $ks = atg_db_number($params['ks'], false);
    $ktt = atg_db_number($params['ktt'], false);
    $kts = atg_db_number($params['kts'], false);
    $ktt_num = atg_num($ktt);
    $kts_num = atg_num($kts);
    $kst = '';
    if ($ktt_num !== null && $kts_num !== null && $ktt_num != 0.0) {
        $kst = rtrim(rtrim(number_format($kts_num / $ktt_num, 12, '.', ''), '0'), '.');
    }
    $temp_fbg_1 = atg_db_number($params['temp_fbg_1'], false);
    $temp_fbg_2 = atg_db_number($params['temp_fbg_2'], false);
    $base_floor = atg_db_number($params['base_floor'], true);
    $mid_floor = atg_db_number($params['mid_floor'], true);
    $roof_floor = atg_db_number($params['roof_floor'], true);
    $creep_t0 = $params['creep_t0'];
    $ts_days = $params['ts'];
    $h0_value = $params['h0'];

    $sql = 'INSERT INTO atg_parameters (
        sensor_id, concrete_grade, sfc, pour_date, rh, ac, ar, u_value, load_value,
        serial_no, ks, ktt, kts, kst, temp_fbg_1, temp_fbg_2, base_floor, mid_floor, roof_floor,
        creep_t0, ts_days, h0_value
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        concrete_grade = VALUES(concrete_grade),
        sfc = VALUES(sfc),
        pour_date = VALUES(pour_date),
        rh = VALUES(rh),
        ac = VALUES(ac),
        ar = VALUES(ar),
        u_value = VALUES(u_value),
        load_value = VALUES(load_value),
        serial_no = VALUES(serial_no),
        ks = VALUES(ks),
        ktt = VALUES(ktt),
        kts = VALUES(kts),
        kst = VALUES(kst),
        temp_fbg_1 = VALUES(temp_fbg_1),
        temp_fbg_2 = VALUES(temp_fbg_2),
        base_floor = VALUES(base_floor),
        mid_floor = VALUES(mid_floor),
        roof_floor = VALUES(roof_floor),
        creep_t0 = VALUES(creep_t0),
        ts_days = VALUES(ts_days),
        h0_value = VALUES(h0_value)';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param(
        $stmt,
        'isssssssssssssssssssss',
        $sensor_id,
        $grade,
        $sfc,
        $pour_date,
        $rh,
        $ac,
        $ar,
        $u_value,
        $load_value,
        $serial_no,
        $ks,
        $ktt,
        $kts,
        $kst,
        $temp_fbg_1,
        $temp_fbg_2,
        $base_floor,
        $mid_floor,
        $roof_floor,
        $creep_t0,
        $ts_days,
        $h0_value
    );
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function atg_write_readings($conn, $sensor_id, $rows) {
    $delete = mysqli_prepare($conn, 'DELETE FROM atg_readings WHERE sensor_id = ?');
    if (!$delete) {
        return false;
    }
    mysqli_stmt_bind_param($delete, 'i', $sensor_id);
    $ok = mysqli_stmt_execute($delete);
    mysqli_stmt_close($delete);
    if (!$ok) {
        return false;
    }

    $sql = 'INSERT INTO atg_readings (sensor_id, row_no, item_name, reading_date, bottom_strain, bottom_temp, top_strain, top_temp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    $row_no = 0;
    $item_name = '';
    $reading_date = '';
    $bottom_strain = '';
    $bottom_temp = '';
    $top_strain = '';
    $top_temp = '';
    mysqli_stmt_bind_param($stmt, 'iissssss', $sensor_id, $row_no, $item_name, $reading_date, $bottom_strain, $bottom_temp, $top_strain, $top_temp);
    foreach ($rows as $row) {
        $row_no = (int) ($row['row_no'] ?? 0);
        if ($row_no < 1) {
            continue;
        }
        $item_name = atg_clean($row['item_name'] ?? '', 100);
        $reading_date = (string) atg_date_int($row['reading_date'] ?? '');
        $bottom_strain = atg_db_number($row['bottom_strain'] ?? '', false);
        $bottom_temp = atg_db_number($row['bottom_temp'] ?? '', false);
        $top_strain = atg_db_number($row['top_strain'] ?? '', false);
        $top_temp = atg_db_number($row['top_temp'] ?? '', false);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }
    }
    mysqli_stmt_close($stmt);
    return true;
}

function atg_write_history($conn, $sensor_id, $rows) {
    $delete = mysqli_prepare($conn, 'DELETE FROM atg_stress_history WHERE sensor_id = ?');
    if (!$delete) {
        return false;
    }
    mysqli_stmt_bind_param($delete, 'i', $sensor_id);
    $ok = mysqli_stmt_execute($delete);
    mysqli_stmt_close($delete);
    if (!$ok) {
        return false;
    }

    $sql = 'INSERT INTO atg_stress_history (sensor_id, row_no, history_date) VALUES (?, ?, ?)';
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    $row_no = 0;
    $history_date = '';
    mysqli_stmt_bind_param($stmt, 'iis', $sensor_id, $row_no, $history_date);
    foreach ($rows as $row) {
        $row_no = (int) ($row['row_no'] ?? 0);
        if ($row_no < 1) {
            continue;
        }
        $history_date = (string) atg_date_int($row['history_date'] ?? '');
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }
    }
    mysqli_stmt_close($stmt);
    return true;
}

function atg_commit_project($conn, $project_id) {
    if (!isset($_SESSION['project_draft'][$project_id]) || !is_array($_SESSION['project_draft'][$project_id])) {
        return true;
    }

    mysqli_begin_transaction($conn);
    foreach ($_SESSION['project_draft'][$project_id] as $sensor_id => $sections) {
        if (!is_array($sections)) {
            continue;
        }
        $sensor_id = (int) $sensor_id;
        if (!atg_sensor_ok($conn, $project_id, $sensor_id)) {
            continue;
        }
        if (isset($sections['parameter']) && is_array($sections['parameter']) && !atg_write_params($conn, $sensor_id, $sections['parameter'])) {
            mysqli_rollback($conn);
            return false;
        }
        if (isset($sections['details']) && is_array($sections['details']) && !atg_write_readings($conn, $sensor_id, $sections['details'])) {
            mysqli_rollback($conn);
            return false;
        }
        if (isset($sections['modulus']) && is_array($sections['modulus']) && !atg_write_history($conn, $sensor_id, $sections['modulus'])) {
            mysqli_rollback($conn);
            return false;
        }
    }
    mysqli_commit($conn);
    unset($_SESSION['project_draft'][$project_id]);
    return true;
}

function atg_save($conn) {
    $project_id = $_POST['project_id'] ?? '';
    $sensor_id = $_POST['sensor_id'] ?? '';
    $form = $_POST['atg_form'] ?? '';
    $save_project = isset($_POST['save_project']);

    if (!ctype_digit((string) $project_id)) {
        header('Location: home.php');
        exit;
    }

    $project_id = (int) $project_id;
    $row_change = isset($_POST['add_row']) || (ctype_digit((string) ($_POST['delete_row'] ?? '')) && (int) $_POST['delete_row'] >= 1);

    if ($form !== '') {
        if (!ctype_digit((string) $sensor_id) || !atg_sensor_ok($conn, $project_id, (int) $sensor_id)) {
            if (!$save_project) {
                header('Location: project.php?id=' . $project_id);
                exit;
            }
        } else {
            atg_store_posted_form($project_id, (int) $sensor_id, $form);
            $sensor_id = (int) $sensor_id;
        }
    }

    if ($save_project) {
        atg_commit_project($conn, $project_id);
        $next = atg_safe_next($_POST['next'] ?? '', $project_id);
        if ($next === 'project.php?id=' . $project_id && $form !== '' && ctype_digit((string) $sensor_id)) {
            $next = atg_section_url($project_id, (int) $sensor_id, $form);
        }
        header('Location: ' . $next);
        exit;
    }

    if ($row_change && $form !== '' && ctype_digit((string) $sensor_id)) {
        header('Location: ' . atg_section_url($project_id, (int) $sensor_id, $form));
        exit;
    }

    header('Location: ' . atg_safe_next($_POST['next'] ?? '', $project_id));
    exit;
}

