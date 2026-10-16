<?php
require_once __DIR__ . '/common.php';
// Measured strain tab formulas.

function atg_measured($readings, $p) {
    $rows = array();
    $index = 0;
    foreach ($readings as $reading) {
        $index = $index + 1;
        $rows[] = array(
            'reading' => $reading,
            'used' => atg_used($reading),
            'row_no' => $index,
            'avg_strain' => atg_avg(atg_num($reading['bottom_strain']), atg_num($reading['top_strain'])),
            'avg_temp' => atg_avg(atg_num($reading['bottom_temp']), atg_num($reading['top_temp'])),
        );
    }

    $first_strain = null;
    $first_temp = null;
    $base_strain = null;
    $base_temp = null;
    if (isset($rows[0])) {
        $first_strain = $rows[0]['avg_strain'];
        $first_temp = $rows[0]['avg_temp'];
    }
    if (isset($rows[1])) {
        $base_strain = $rows[1]['avg_strain'];
        $base_temp = $rows[1]['avg_temp'];
    }

    foreach ($rows as $index => $row) {
        if ($row['used'] == false) {
            $row['d_strain'] = null;
            $row['d_temp'] = null;
        } else if ($row['row_no'] == 1) {
            $row['d_strain'] = 0;
            $row['d_temp'] = 0;
        } else {
            if ($row['avg_strain'] === null) {
                $row['d_strain'] = null;
            } else if ($first_strain === null) {
                $row['d_strain'] = null;
            } else {
                $row['d_strain'] = $row['avg_strain'] - $first_strain;
            }
            if ($row['avg_temp'] === null) {
                $row['d_temp'] = null;
            } else if ($first_temp === null) {
                $row['d_temp'] = null;
            } else {
                $row['d_temp'] = $row['avg_temp'] - $first_temp;
            }
        }

        $row['temp_c'] = null;
        if ($row['avg_temp'] === null) {
            $row['temp_c'] = null;
        } else if ($p['fbg2'] === null) {
            $row['temp_c'] = null;
        } else if ($p['fbg2'] == 0) {
            $row['temp_c'] = null;
        } else {
            $fbg_base = 0;
            if ($p['fbg1'] !== null) {
                $fbg_base = $p['fbg1'];
            }
            $row['temp_c'] = round(($row['avg_temp'] - $fbg_base) / $p['fbg2'], 1);
        }

        if ($row['row_no'] == 1) {
            $strain_base = $row['avg_strain'];
            $temp_base = $row['avg_temp'];
        } else {
            $strain_base = $base_strain;
            $temp_base = $base_temp;
        }

        $row['measured'] = null;
        if ($row['avg_strain'] === null) {
            $row['measured'] = null;
        } else if ($row['avg_temp'] === null) {
            $row['measured'] = null;
        } else if ($strain_base === null) {
            $row['measured'] = null;
        } else if ($temp_base === null) {
            $row['measured'] = null;
        } else if ($p['kst'] === null) {
            $row['measured'] = null;
        } else if ($p['ks'] === null) {
            $row['measured'] = null;
        } else if ($p['ks'] == 0) {
            $row['measured'] = null;
        } else {
            $row['measured'] = (($row['avg_strain'] - $strain_base) - ($row['avg_temp'] - $temp_base) * $p['kst']) / $p['ks'];
        }

        $rows[$index] = $row;
    }

    return $rows;
}

