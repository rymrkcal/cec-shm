<?php
require_once __DIR__ . '/common.php';
// Measured strain tab. First row is 0. Later rows use the first row as the reference.
// measured = (CS × CC × dStrain) − (ctTerm × CTEA) + (ctTerm × CTEB × L1) + (ctTerm × CTEC × L2)
// ctTerm = CT × CC × dTemp
// °C = CT × ((average temp − T wavelength) × CC) + Temp + Temperature

function l3_measured($readings, $p) {
    $rows = array();
    $index = 0;
    foreach ($readings as $reading) {
        $index = $index + 1;
        $rows[] = array(
            'reading' => $reading,
            'used' => l3_used($reading),
            'row_no' => $index,
            'avg_strain' => l3_avg(l3_num($reading['bottom_strain']), l3_num($reading['top_strain'])),
            'avg_temp' => l3_avg(l3_num($reading['bottom_temp']), l3_num($reading['top_temp'])),
        );
    }

    $first_strain = null;
    $first_temp = null;
    if (isset($rows[0])) {
        $first_strain = $rows[0]['avg_strain'];
        $first_temp = $rows[0]['avg_temp'];
    }

    foreach ($rows as $index => $row) {
        if ($row['used'] == false) {
            $row['d_strain'] = null;
            $row['d_temp'] = null;
        } else if ($row['row_no'] == 1) {
            $row['d_strain'] = 0;
            $row['d_temp'] = 0;
        } else {
            if ($row['avg_strain'] === null || $first_strain === null) {
                $row['d_strain'] = null;
            } else {
                $row['d_strain'] = $row['avg_strain'] - $first_strain;
            }
            if ($row['avg_temp'] === null || $first_temp === null) {
                $row['d_temp'] = null;
            } else {
                $row['d_temp'] = $row['avg_temp'] - $first_temp;
            }
        }

        $row['temp_c'] = null;
        if ($row['avg_temp'] !== null && $p['ct'] !== null && $p['cc'] !== null && $p['twave'] !== null && $p['temp_offset'] !== null && $p['temperature'] !== null) {
            $row['temp_c'] = ($p['ct'] * (($row['avg_temp'] - $p['twave']) * $p['cc']) + $p['temp_offset']) + $p['temperature'];
        }

        $row['measured'] = null;
        $has_average = $row['avg_strain'] !== null && $row['avg_temp'] !== null && $first_strain !== null && $first_temp !== null;
        if ($has_average && $row['row_no'] == 1) {
            $row['measured'] = 0;
        } else if ($has_average && $row['d_strain'] !== null && $row['d_temp'] !== null && $p['cs'] !== null && $p['ct'] !== null && $p['ctea'] !== null && $p['cteb'] !== null && $p['ctec'] !== null && $p['l1'] !== null && $p['l2'] !== null && $p['cc'] !== null) {
            $ct_term = $p['ct'] * $p['cc'] * $row['d_temp'];
            $row['measured'] = ($p['cs'] * $p['cc'] * $row['d_strain']) - ($ct_term * $p['ctea']) + ($ct_term * $p['cteb'] * $p['l1']) + ($ct_term * $p['ctec'] * $p['l2']);
        }

        $rows[$index] = $row;
    }

    return $rows;
}

