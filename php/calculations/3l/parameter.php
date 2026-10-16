<?php
require_once __DIR__ . '/common.php';
// Parameter tab formulas.

function l3_grade($grade) {
    $grade = trim((string) $grade);
    if ($grade === '40') {
        return array('grade' => '40', 'fck' => 32.0, 'ec' => 28.0);
    } else if ($grade === '50') {
        return array('grade' => '50', 'fck' => 40.0, 'ec' => 30.0);
    } else if ($grade === '60') {
        return array('grade' => '60', 'fck' => 50.0, 'ec' => 32.0);
    }
    return null;
}

function l3_parameter($params, $history) {
    $grade = l3_grade($params['concrete_grade']);
    $grade_name = '';
    $fck = null;
    $ec_table = null;
    if ($grade !== null) {
        $grade_name = $grade['grade'];
        $fck = $grade['fck'];
        $ec_table = $grade['ec'];
    }

    $fcm = null;
    if ($fck !== null) {
        $fcm = $fck + 8;
    }

    $sfc = l3_num($params['sfc']);
    $ac = l3_num($params['ac']);
    $ar = l3_num($params['ar']);
    $u = l3_num($params['u']);
    $load = l3_num($params['load_value']);
    $rh = l3_num($params['rh']);
    $base_floor = l3_num($params['base_floor']);
    $mid_floor = l3_num($params['mid_floor']);
    $roof_floor = l3_num($params['roof_floor']);

    $cs = l3_num($params['cs']);
    $ct = l3_num($params['ct']);
    $ctea = 14.1;
    $cteb = 17.3;
    $ctec = 16.4;
    $l1 = 0.006;
    $l2 = 0.03;
    $cc = 1000;
    $twave = l3_num($params['t_wave']);
    $temperature = l3_num($params['temperature']);
    $temp_offset = l3_num($params['temp_offset']);

    $creep_t0 = 28;
    $ts = 0;
    $h0 = 100;

    $h = null;
    if ($ac !== null && $u !== null && $u != 0) {
        $h = 2 * $ac / $u;
    }

    $eequ = null;
    if ($fck !== null && $ac !== null && $ar !== null && $sfc !== null && $sfc != 0 && ($ac + $ar) != 0) {
        $eequ = (($fck * $ac / $sfc) + (200 * $ar)) / ($ac + $ar);
    }

    $strength = null;
    $eq_area = null;
    if ($ac !== null && $ar !== null && $ec_table !== null && $ec_table != 0) {
        $strength = $ec_table * $ac + 200 * $ar;
        $eq_area = $strength / $ec_table;
    }

    $base_stress = null;
    if ($load !== null && $base_floor !== null) {
        $base_stress = $load * $base_floor;
    }
    $mid_stress = null;
    if ($load !== null && $mid_floor !== null) {
        $mid_stress = $load * $mid_floor;
    }
    $roof_stress = null;
    if ($load !== null && $roof_floor !== null) {
        $roof_stress = $load * $roof_floor;
    }

    return array(
        'grade_name' => $grade_name,
        'fck' => $fck,
        'fcm' => $fcm,
        'ec_table' => $ec_table,
        'h' => $h,
        'eequ' => $eequ,
        'eq_area' => $eq_area,
        'strength' => $strength,
        'base_date' => l3_floor_date($history, $base_floor),
        'mid_date' => l3_floor_date($history, $mid_floor),
        'roof_date' => l3_floor_date($history, $roof_floor),
        'base_stress' => $base_stress,
        'mid_stress' => $mid_stress,
        'roof_stress' => $roof_stress,
        'rh' => $rh,
        'load' => $load,
        'cs' => $cs,
        'ct' => $ct,
        'ctea' => $ctea,
        'cteb' => $cteb,
        'ctec' => $ctec,
        'l1' => $l1,
        'l2' => $l2,
        'cc' => $cc,
        'twave' => $twave,
        'temperature' => $temperature,
        'temp_offset' => $temp_offset,
        'base_floor' => $base_floor,
        'mid_floor' => $mid_floor,
        'roof_floor' => $roof_floor,
        'creep_t0' => $creep_t0,
        'ts' => $ts,
        'h0' => $h0,
    );
}

