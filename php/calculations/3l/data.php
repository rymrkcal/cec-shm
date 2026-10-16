<?php
require_once __DIR__ . '/common.php';
// 3L load, draft, and save.

function l3_max_row($conn, $sensor_id, $table) {
    if ($table === 'history') {
        $sql = 'SELECT MAX(row_no) AS max_row FROM 3l_stress_history WHERE sensor_id = ?';
    } else {
        $sql = 'SELECT MAX(row_no) AS max_row FROM 3l_readings WHERE sensor_id = ?';
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

function l3_saved_grade($value) {
    $value = trim((string) $value);
    if ($value === '32') {
        return '40';
    }
    if ($value === '40' || $value === '50' || $value === '60') {
        return $value;
    }
    return '';
}

function l3_load_params($conn, $sensor_id) {
    $params = l3_default_params();
    $stmt = mysqli_prepare($conn, 'SELECT fck, sfc, pour_date, rh, ac, ar, u_value, load_value, serial_no, cs, ct, ctea, cteb, ctec, l1_value, l2_value, cc, t_wave, temperature, temp_offset, base_floor, mid_floor, roof_floor, creep_t0, ts_days, h0_value FROM 3l_parameters WHERE sensor_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $sensor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row) {
        return $params;
    }

    $params['concrete_grade'] = l3_saved_grade(l3_stored($row['fck']));
    $params['sfc'] = l3_stored($row['sfc']);
    $params['pour_date'] = l3_stored($row['pour_date']);
    $params['rh'] = l3_stored($row['rh']);
    $params['ac'] = l3_stored($row['ac']);
    $params['ar'] = l3_stored($row['ar']);
    $params['u'] = l3_stored($row['u_value']);
    $params['load_value'] = l3_stored($row['load_value']);
    $params['serial_no'] = l3_stored($row['serial_no']);
    $params['cs'] = l3_stored($row['cs']);
    $params['ct'] = l3_stored($row['ct']);
    $params['ctea'] = '14.1';
    $params['cteb'] = '17.3';
    $params['ctec'] = '16.4';
    $params['l1'] = '0.006';
    $params['l2'] = '0.03';
    $params['cc'] = '1000';
    $params['t_wave'] = l3_stored($row['t_wave']);
    $params['temperature'] = l3_stored($row['temperature']);
    $params['temp_offset'] = l3_stored($row['temp_offset']);
    $params['base_floor'] = l3_stored($row['base_floor']);
    $params['mid_floor'] = l3_stored($row['mid_floor']);
    $params['roof_floor'] = l3_stored($row['roof_floor']);
    $params['creep_t0'] = '28';
    $params['ts'] = '0';
    $params['h0'] = '100';
    return $params;
}

function l3_load_readings($conn, $sensor_id) {
    $by_no = array();
    $stmt = mysqli_prepare($conn, 'SELECT row_no, item_name, reading_date, bottom_strain, bottom_temp, top_strain, top_temp FROM 3l_readings WHERE sensor_id = ? ORDER BY row_no');
    mysqli_stmt_bind_param($stmt, 'i', $sensor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['item_name'] = l3_stored($row['item_name']);
        $row['reading_date'] = l3_stored($row['reading_date']);
        $row['bottom_strain'] = l3_stored($row['bottom_strain']);
        $row['bottom_temp'] = l3_stored($row['bottom_temp']);
        $row['top_strain'] = l3_stored($row['top_strain']);
        $row['top_temp'] = l3_stored($row['top_temp']);
        $row['stress_date'] = '';
        $by_no[(int) $row['row_no']] = $row;
    }
    mysqli_stmt_close($stmt);

    $readings = array();
    $count = l3_max_row($conn, $sensor_id, 'readings');
    for ($row_no = 1; $row_no <= $count; $row_no++) {
        if (isset($by_no[$row_no])) {
            $readings[] = $by_no[$row_no];
        } else {
            $readings[] = l3_blank_reading($row_no);
        }
    }
    return $readings;
}

function l3_load_history($conn, $sensor_id) {
    $by_no = array();
    $stmt = mysqli_prepare($conn, 'SELECT row_no, history_date FROM 3l_stress_history WHERE sensor_id = ? ORDER BY row_no');
    mysqli_stmt_bind_param($stmt, 'i', $sensor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['history_date'] = l3_stored($row['history_date']);
        $by_no[(int) $row['row_no']] = $row;
    }
    mysqli_stmt_close($stmt);

    $history = array();
    $count = l3_max_row($conn, $sensor_id, 'history');
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

function l3_sensor_ok($conn, $project_id, $sensor_id) {
    $stmt = mysqli_prepare($conn, 'SELECT sensors.sensor_id FROM sensors JOIN blocks ON blocks.block_id = sensors.block_id WHERE sensors.sensor_id = ? AND blocks.project_id = ? AND sensors.formula = "3L"');
    mysqli_stmt_bind_param($stmt, 'ii', $sensor_id, $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? true : false;
}

function l3_draft_section($project_id, $sensor_id, $section) {
    $project_id = (int) $project_id;
    $sensor_id = (int) $sensor_id;
    if (!isset($_SESSION['l3_draft'][$project_id][$sensor_id][$section]) || !is_array($_SESSION['l3_draft'][$project_id][$sensor_id][$section])) {
        return null;
    }
    return $_SESSION['l3_draft'][$project_id][$sensor_id][$section];
}

function l3_draft_put($project_id, $sensor_id, $section, $data) {
    $_SESSION['l3_draft'][(int) $project_id][(int) $sensor_id][$section] = $data;
}

function l3_pad_rows($rows, $kind) {
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
            $padded[] = l3_blank_reading($row_no);
        }
    }
    return $padded;
}

function l3_posted_count($prefix) {
    $count = 0;
    $pattern = '/^' . preg_quote($prefix, '/') . '_(\d+)$/';
    foreach ($_POST as $key => $value) {
        if (preg_match($pattern, (string) $key, $match)) {
            $count = max($count, (int) $match[1]);
        }
    }
    return $count;
}

function l3_renumber_rows($rows, $drop) {
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

function l3_params_from_post() {
    $params = l3_default_params();
    $grade = l3_clean($_POST['concrete_grade'] ?? '', 20);
    if ($grade !== '40' && $grade !== '50' && $grade !== '60') {
        $grade = '';
    }
    $params['concrete_grade'] = $grade;
    $params['sfc'] = l3_clean($_POST['sfc'] ?? '', 40);
    $params['pour_date'] = l3_clean($_POST['pour_date'] ?? '', 40);
    $params['rh'] = l3_clean($_POST['rh'] ?? '', 40);
    $params['ac'] = l3_clean($_POST['ac'] ?? '', 40);
    $params['ar'] = l3_clean($_POST['ar'] ?? '', 40);
    $params['u'] = l3_clean($_POST['u'] ?? '', 40);
    $params['load_value'] = l3_clean($_POST['load_value'] ?? '', 40);
    $params['serial_no'] = l3_clean($_POST['serial_no'] ?? '', 50);
    $params['cs'] = l3_clean($_POST['cs'] ?? '', 40);
    $params['ct'] = l3_clean($_POST['ct'] ?? '', 40);
    $params['ctea'] = '14.1';
    $params['cteb'] = '17.3';
    $params['ctec'] = '16.4';
    $params['l1'] = '0.006';
    $params['l2'] = '0.03';
    $params['cc'] = '1000';
    $params['t_wave'] = l3_clean($_POST['t_wave'] ?? '', 40);
    $params['temperature'] = l3_clean($_POST['temperature'] ?? '', 40);
    $params['temp_offset'] = l3_clean($_POST['temp_offset'] ?? '', 40);
    $params['base_floor'] = l3_clean($_POST['base_floor'] ?? '', 20);
    $params['mid_floor'] = l3_clean($_POST['mid_floor'] ?? '', 20);
    $params['roof_floor'] = l3_clean($_POST['roof_floor'] ?? '', 20);
    $params['creep_t0'] = '28';
    $params['ts'] = '0';
    $params['h0'] = '100';
    return $params;
}

function l3_details_from_post() {
    $count = l3_posted_count('item_name');
    $rows = array();
    for ($row_no = 1; $row_no <= $count; $row_no++) {
        $rows[] = array(
            'row_no' => $row_no,
            'item_name' => l3_clean($_POST['item_name_' . $row_no] ?? '', 100),
            'reading_date' => l3_store_date(l3_clean($_POST['reading_date_' . $row_no] ?? '', 40)),
            'bottom_strain' => l3_clean($_POST['bottom_strain_' . $row_no] ?? '', 40),
            'bottom_temp' => l3_clean($_POST['bottom_temp_' . $row_no] ?? '', 40),
            'top_strain' => l3_clean($_POST['top_strain_' . $row_no] ?? '', 40),
            'top_temp' => l3_clean($_POST['top_temp_' . $row_no] ?? '', 40),
            'stress_date' => '',
        );
    }
    $delete_row = $_POST['delete_row'] ?? '';
    if (ctype_digit((string) $delete_row) && (int) $delete_row >= 1) {
        $rows = l3_renumber_rows($rows, (int) $delete_row);
    } elseif (isset($_POST['add_row'])) {
        $rows[] = l3_blank_reading(count($rows) + 1);
    }
    return $rows;
}

function l3_history_from_post() {
    $count = l3_posted_count('stress_date');
    $rows = array();
    for ($row_no = 1; $row_no <= $count; $row_no++) {
        $rows[] = array(
            'row_no' => $row_no,
            'history_date' => l3_store_date(l3_clean($_POST['stress_date_' . $row_no] ?? '', 40)),
        );
    }
    $delete_row = $_POST['delete_row'] ?? '';
    if (ctype_digit((string) $delete_row) && (int) $delete_row >= 1) {
        $rows = l3_renumber_rows($rows, (int) $delete_row);
    } elseif (isset($_POST['add_row'])) {
        $rows[] = array(
            'row_no' => count($rows) + 1,
            'history_date' => '',
        );
    }
    return $rows;
}

function l3_store_posted_form($project_id, $sensor_id, $form) {
    if ($form === 'parameter') {
        l3_draft_put($project_id, $sensor_id, 'parameter', l3_params_from_post());
    } elseif ($form === 'details') {
        l3_draft_put($project_id, $sensor_id, 'details', l3_details_from_post());
    } elseif ($form === 'modulus') {
        l3_draft_put($project_id, $sensor_id, 'modulus', l3_history_from_post());
    }
}

function l3_safe_next($next, $project_id) {
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

function l3_section_url($project_id, $sensor_id, $form) {
    $url = 'project.php?id=' . (int) $project_id . '&sensor_id=' . (int) $sensor_id;
    if ($form === 'details') {
        return $url . '&tab=details';
    }
    if ($form === 'modulus') {
        return $url . '&tab=theoretical&part=modulus';
    }
    return $url . '&tab=parameter';
}

function l3_write_params($conn, $sensor_id, $params) {
    $params = array_merge(l3_default_params(), $params);
    $fck = l3_db_number($params['concrete_grade'], true);
    $sfc = l3_db_number($params['sfc'], false);
    $pour_date = l3_clean($params['pour_date'], 40);
    $rh = l3_db_number($params['rh'], true);
    $ac = l3_db_number($params['ac'], false);
    $ar = l3_db_number($params['ar'], false);
    $u_value = l3_db_number($params['u'], true);
    $load_value = l3_db_number($params['load_value'], false);
    $serial_no = l3_clean($params['serial_no'], 50);
    $cs = l3_db_number($params['cs'], false);
    $ct = l3_db_number($params['ct'], false);
    $ctea = '14.1';
    $cteb = '17.3';
    $ctec = '16.4';
    $l1_value = '0.006';
    $l2_value = '0.03';
    $cc = '1000';
    $t_wave = l3_db_number($params['t_wave'], false);
    $temperature = l3_db_number($params['temperature'], false);
    $temp_offset = l3_db_number($params['temp_offset'], false);
    $base_floor = l3_db_number($params['base_floor'], true);
    $mid_floor = l3_db_number($params['mid_floor'], true);
    $roof_floor = l3_db_number($params['roof_floor'], true);
    $creep_t0 = '28';
    $ts_days = '0';
    $h0_value = '100';

    $sql = 'INSERT INTO 3l_parameters (
        sensor_id, fck, sfc, pour_date, rh, ac, ar, u_value, load_value,
        serial_no, cs, ct, ctea, cteb, ctec, l1_value, l2_value, cc, t_wave, temperature, temp_offset,
        base_floor, mid_floor, roof_floor, creep_t0, ts_days, h0_value
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        fck = VALUES(fck),
        sfc = VALUES(sfc),
        pour_date = VALUES(pour_date),
        rh = VALUES(rh),
        ac = VALUES(ac),
        ar = VALUES(ar),
        u_value = VALUES(u_value),
        load_value = VALUES(load_value),
        serial_no = VALUES(serial_no),
        cs = VALUES(cs),
        ct = VALUES(ct),
        ctea = VALUES(ctea),
        cteb = VALUES(cteb),
        ctec = VALUES(ctec),
        l1_value = VALUES(l1_value),
        l2_value = VALUES(l2_value),
        cc = VALUES(cc),
        t_wave = VALUES(t_wave),
        temperature = VALUES(temperature),
        temp_offset = VALUES(temp_offset),
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
        'issssssssssssssssssssssssss',
        $sensor_id,
        $fck,
        $sfc,
        $pour_date,
        $rh,
        $ac,
        $ar,
        $u_value,
        $load_value,
        $serial_no,
        $cs,
        $ct,
        $ctea,
        $cteb,
        $ctec,
        $l1_value,
        $l2_value,
        $cc,
        $t_wave,
        $temperature,
        $temp_offset,
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

function l3_write_readings($conn, $sensor_id, $rows) {
    $delete = mysqli_prepare($conn, 'DELETE FROM 3l_readings WHERE sensor_id = ?');
    if (!$delete) {
        return false;
    }
    mysqli_stmt_bind_param($delete, 'i', $sensor_id);
    $ok = mysqli_stmt_execute($delete);
    mysqli_stmt_close($delete);
    if (!$ok) {
        return false;
    }

    $sql = 'INSERT INTO 3l_readings (sensor_id, row_no, item_name, reading_date, bottom_strain, bottom_temp, top_strain, top_temp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
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
        $item_name = l3_clean($row['item_name'] ?? '', 100);
        $reading_date = (string) l3_date_int($row['reading_date'] ?? '');
        $bottom_strain = l3_db_number($row['bottom_strain'] ?? '', false);
        $bottom_temp = l3_db_number($row['bottom_temp'] ?? '', false);
        $top_strain = l3_db_number($row['top_strain'] ?? '', false);
        $top_temp = l3_db_number($row['top_temp'] ?? '', false);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }
    }
    mysqli_stmt_close($stmt);
    return true;
}

function l3_write_history($conn, $sensor_id, $rows) {
    $delete = mysqli_prepare($conn, 'DELETE FROM 3l_stress_history WHERE sensor_id = ?');
    if (!$delete) {
        return false;
    }
    mysqli_stmt_bind_param($delete, 'i', $sensor_id);
    $ok = mysqli_stmt_execute($delete);
    mysqli_stmt_close($delete);
    if (!$ok) {
        return false;
    }

    $sql = 'INSERT INTO 3l_stress_history (sensor_id, row_no, history_date) VALUES (?, ?, ?)';
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
        $history_date = (string) l3_date_int($row['history_date'] ?? '');
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }
    }
    mysqli_stmt_close($stmt);
    return true;
}

function l3_commit_project($conn, $project_id) {
    if (!isset($_SESSION['l3_draft'][$project_id]) || !is_array($_SESSION['l3_draft'][$project_id])) {
        return true;
    }

    mysqli_begin_transaction($conn);
    foreach ($_SESSION['l3_draft'][$project_id] as $sensor_id => $sections) {
        if (!is_array($sections)) {
            continue;
        }
        $sensor_id = (int) $sensor_id;
        if (!l3_sensor_ok($conn, $project_id, $sensor_id)) {
            continue;
        }
        if (isset($sections['parameter']) && is_array($sections['parameter']) && !l3_write_params($conn, $sensor_id, $sections['parameter'])) {
            mysqli_rollback($conn);
            return false;
        }
        if (isset($sections['details']) && is_array($sections['details']) && !l3_write_readings($conn, $sensor_id, $sections['details'])) {
            mysqli_rollback($conn);
            return false;
        }
        if (isset($sections['modulus']) && is_array($sections['modulus']) && !l3_write_history($conn, $sensor_id, $sections['modulus'])) {
            mysqli_rollback($conn);
            return false;
        }
    }
    mysqli_commit($conn);
    unset($_SESSION['l3_draft'][$project_id]);
    return true;
}

function l3_save($conn) {
    $project_id = $_POST['project_id'] ?? '';
    $sensor_id = $_POST['sensor_id'] ?? '';
    $form = $_POST['l3_form'] ?? '';
    $save_project = isset($_POST['save_project']);

    if (!ctype_digit((string) $project_id)) {
        header('Location: home.php');
        exit;
    }

    $project_id = (int) $project_id;
    $row_change = isset($_POST['add_row']) || (ctype_digit((string) ($_POST['delete_row'] ?? '')) && (int) $_POST['delete_row'] >= 1);

    if ($form !== '') {
        if (!ctype_digit((string) $sensor_id) || !l3_sensor_ok($conn, $project_id, (int) $sensor_id)) {
            if (!$save_project) {
                header('Location: project.php?id=' . $project_id);
                exit;
            }
        } else {
            l3_store_posted_form($project_id, (int) $sensor_id, $form);
            $sensor_id = (int) $sensor_id;
        }
    }

    if ($save_project) {
        l3_commit_project($conn, $project_id);
        $next = l3_safe_next($_POST['next'] ?? '', $project_id);
        if ($next === 'project.php?id=' . $project_id && $form !== '' && ctype_digit((string) $sensor_id)) {
            $next = l3_section_url($project_id, (int) $sensor_id, $form);
        }
        header('Location: ' . $next);
        exit;
    }

    if ($row_change && $form !== '' && ctype_digit((string) $sensor_id)) {
        header('Location: ' . l3_section_url($project_id, (int) $sensor_id, $form));
        exit;
    }

    header('Location: ' . l3_safe_next($_POST['next'] ?? '', $project_id));
    exit;
}

