<?php
require_once __DIR__ . '/common.php';
// Parameter tab formulas.

function atg_grade($grade) {
    $grade = trim((string) $grade);
    if ($grade === '40') {
        return array('fck' => 32.0, 'ec' => 28.0);
    } else if ($grade === '50') {
        return array('fck' => 40.0, 'ec' => 30.0);
    } else if ($grade === '60') {
        return array('fck' => 50.0, 'ec' => 32.0);
    }
    return null;
}

function atg_parameter($params, $history) {
    $grade = atg_grade($params['concrete_grade']);
    $fck = null;
    $ec_table = null;
    if ($grade === null) {
        $fck = null;
        $ec_table = null;
    } else {
        $fck = $grade['fck'];
        $ec_table = $grade['ec'];
    }

    $fcm = null;
    if ($fck === null) {
        $fcm = null;
    } else {
        $fcm = $fck + 8;
    }

    $sfc = atg_num($params['sfc']);
    $ac = atg_num($params['ac']);
    $ar = atg_num($params['ar']);
    $u = atg_num($params['u']);
    $load = atg_num($params['load_value']);
    $rh = atg_num($params['rh']);
    $ks = atg_num($params['ks']);
    $ktt = atg_num($params['ktt']);
    $kts = atg_num($params['kts']);
    $fbg1 = atg_num($params['temp_fbg_1']);
    $fbg2 = atg_num($params['temp_fbg_2']);
    $base_floor = atg_num($params['base_floor']);
    $mid_floor = atg_num($params['mid_floor']);
    $roof_floor = atg_num($params['roof_floor']);

    $creep_t0 = 28;
    $ts = 0;
    $h0 = 100;

    $h = null;
    if ($ac === null) {
        $h = null;
    } else if ($u === null) {
        $h = null;
    } else if ($u == 0) {
        $h = null;
    } else {
        $h = 2 * $ac / $u;
    }

    $eequ = null;
    if ($fck === null) {
        $eequ = null;
    } else if ($ac === null) {
        $eequ = null;
    } else if ($ar === null) {
        $eequ = null;
    } else if ($sfc === null) {
        $eequ = null;
    } else if ($sfc == 0) {
        $eequ = null;
    } else if (($ac + $ar) == 0) {
        $eequ = null;
    } else {
        $eequ = (($fck * $ac / $sfc) + (200 * $ar)) / ($ac + $ar);
    }

    $strength = null;
    $eq_area = null;
    if ($ac === null) {
        $strength = null;
        $eq_area = null;
    } else if ($ar === null) {
        $strength = null;
        $eq_area = null;
    } else if ($ec_table === null) {
        $strength = null;
        $eq_area = null;
    } else if ($ec_table == 0) {
        $strength = null;
        $eq_area = null;
    } else {
        $strength = $ec_table * $ac + 200 * $ar;
        $eq_area = $strength / $ec_table;
    }

    $kst = null;
    if ($ktt === null) {
        $kst = null;
    } else if ($kts === null) {
        $kst = null;
    } else if ($ktt == 0) {
        $kst = null;
    } else {
        $kst = $kts / $ktt;
    }

    $base_stress = null;
    if ($load === null) {
        $base_stress = null;
    } else if ($base_floor === null) {
        $base_stress = null;
    } else {
        $base_stress = $load * $base_floor;
    }

    $mid_stress = null;
    if ($load === null) {
        $mid_stress = null;
    } else if ($mid_floor === null) {
        $mid_stress = null;
    } else {
        $mid_stress = $load * $mid_floor;
    }

    $roof_stress = null;
    if ($load === null) {
        $roof_stress = null;
    } else if ($roof_floor === null) {
        $roof_stress = null;
    } else {
        $roof_stress = $load * $roof_floor;
    }

    return array(
        'fck' => $fck,
        'fcm' => $fcm,
        'ec_table' => $ec_table,
        'h' => $h,
        'eequ' => $eequ,
        'eq_area' => $eq_area,
        'strength' => $strength,
        'kst' => $kst,
        'kst_ratio' => $kst,
        'base_date' => atg_floor_date($history, $base_floor),
        'mid_date' => atg_floor_date($history, $mid_floor),
        'roof_date' => atg_floor_date($history, $roof_floor),
        'base_stress' => $base_stress,
        'mid_stress' => $mid_stress,
        'roof_stress' => $roof_stress,
        'rh' => $rh,
        'load' => $load,
        'ks' => $ks,
        'fbg1' => $fbg1,
        'fbg2' => $fbg2,
        'base_floor' => $base_floor,
        'mid_floor' => $mid_floor,
        'roof_floor' => $roof_floor,
        'creep_t0' => $creep_t0,
        'ts' => $ts,
        'h0' => $h0,
    );
}

