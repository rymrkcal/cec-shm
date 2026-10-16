<?php
require_once __DIR__ . '/common.php';
// Theoretical strain tab: modulus, notional shrinkage, and creep.

function l3_floor_stress($load, $floors) {
    if ($load === null) {
        return null;
    }
    if ($floors === null) {
        return null;
    }
    return $load * $floors;
}

function l3_loads($row_no, $p) {
    // Spreadsheet output: creep is base, then mid, then roof.
    // Elastic is 0, then base, then mid, then roof.
    $base = l3_floor_stress($p['load'], $p['base_floor']);
    $mid = l3_floor_stress($p['load'], $p['mid_floor']);
    $roof = l3_floor_stress($p['load'], $p['roof_floor']);
    if ($row_no == 1) {
        return array($base, 0);
    }
    if ($row_no == 2) {
        return array($mid, $base);
    }
    if ($row_no == 3) {
        return array($roof, $mid);
    }
    return array($roof, $roof);
}

function l3_eci($fck) {
    if ($fck === null) {
        return null;
    }
    if ($fck <= 0) {
        return null;
    }
    return 21500 * pow($fck / 8, 1 / 3);
}

function l3_ec_mod($eci) {
    if ($eci === null) {
        return null;
    }
    return 0.85 * $eci;
}

function l3_time_modulus($serial, $creep_t0, $eci) {
    $beta_cc = null;
    $beta_e = null;
    $eci_t = null;
    // Spreadsheet: βcc = EXP(0.25 * (1 - SQRT(t0 / (date serial - 1)))).
    if ($serial === null) {
        return array($beta_cc, $beta_e, $eci_t);
    }

    $den = $serial - 1;
    if ($den <= 0) {
        return array($beta_cc, $beta_e, $eci_t);
    }

    $inside = $creep_t0 / $den;
    if ($inside < 0) {
        return array($beta_cc, $beta_e, $eci_t);
    }

    $beta_cc = exp(0.25 * (1 - sqrt($inside)));
    $beta_e = sqrt($beta_cc);
    if ($eci === null) {
        $eci_t = null;
    } else {
        $eci_t = $beta_e * $eci;
    }
    return array($beta_cc, $beta_e, $eci_t);
}

function l3_modulus($params, $history, $p) {
    $eci = l3_eci($p['fck']);
    $ec_mod = l3_ec_mod($eci);
    $rows = array();
    $index = 0;
    foreach ($history as $item) {
        $index = $index + 1;
        $date = trim((string) $item['history_date']);
        $used = true;
        if ($date === '' || $date === '0') {
            $used = false;
        }

        $age = l3_age($date, $params['pour_date']);
        $time = l3_time_modulus(l3_serial($date), $p['creep_t0'], $eci);
        $total_load = null;
        if ($p['load'] !== null) {
            $total_load = $index * $p['load'];
        }

        $rows[] = array(
            'row_no' => $index,
            'used' => $used,
            'reading' => array(
                'stress_date' => $item['history_date'],
                'reading_date' => $item['history_date'],
                'item_name' => '',
            ),
            'age' => $age,
            'total_load' => $total_load,
            'stress_inc' => $p['load'],
            'eci' => $eci,
            'ec_mod' => $ec_mod,
            'beta_cc' => $time[0],
            'beta_e' => $time[1],
            'eci_t' => $time[2],
        );
    }
    return $rows;
}

function l3_shrinkage($rows, $params, $p) {
    $eps_s = null;
    if ($p['fcm'] === null) {
        $eps_s = null;
    } else {
        $eps_s = (160 + 10 * 5 * (9 - $p['fcm'] / 10)) * 1e-6;
    }

    $beta_srh = null;
    $beta_rh = null;
    if ($p['rh'] === null) {
        $beta_srh = null;
        $beta_rh = null;
    } else {
        $beta_srh = 1 - pow($p['rh'] / 100, 3);
        $beta_rh = -1.55 * $beta_srh;
    }

    $eps_cs0 = null;
    if ($eps_s === null) {
        $eps_cs0 = null;
    } else if ($beta_rh === null) {
        $eps_cs0 = null;
    } else {
        $eps_cs0 = $eps_s * $beta_rh;
    }

    foreach ($rows as $index => $row) {
        $row['age'] = l3_age($row['reading']['reading_date'], $params['pour_date']);
        $row['eps_s'] = $eps_s;
        $row['beta_srh'] = $beta_srh;
        $row['beta_rh'] = $beta_rh;
        $row['eps_cs0'] = $eps_cs0;
        $row['beta_s'] = null;
        $row['eps_cs'] = null;

        if ($row['age'] === null) {
            $row['beta_s'] = null;
        } else if ($p['h'] === null) {
            $row['beta_s'] = null;
        } else if ($eps_cs0 === null) {
            $row['beta_s'] = null;
        } else if ($p['h0'] == 0) {
            $row['beta_s'] = null;
        } else {
            $shrink_t = $row['age'] - $p['ts'];
            $den = 350 * pow($p['h'] / $p['h0'], 2) + $shrink_t;
            if ($den == 0) {
                $row['beta_s'] = null;
            } else {
                $ratio = $shrink_t / $den;
                if ($ratio < 0) {
                    $row['beta_s'] = null;
                } else {
                    $row['beta_s'] = sqrt($ratio);
                    $row['eps_cs'] = $eps_cs0 * $row['beta_s'] * 1e6;
                }
            }
        }

        $rows[$index] = $row;
    }

    return $rows;
}

function l3_creep($rows, $params, $history, $p) {
    $eci = l3_eci($p['fck']);
    $beta_t0 = 1 / (0.1 + pow($p['creep_t0'] / 1, 0.2));

    $phi_rh = null;
    $beta_h = null;
    if ($p['rh'] === null) {
        $phi_rh = null;
        $beta_h = null;
    } else if ($p['h'] === null) {
        $phi_rh = null;
        $beta_h = null;
    } else if ($p['h'] <= 0) {
        $phi_rh = null;
        $beta_h = null;
    } else if ($p['h0'] == 0) {
        $phi_rh = null;
        $beta_h = null;
    } else {
        $phi_rh = 1 + ((1 - $p['rh'] / 100) / (0.46 * pow($p['h'] / $p['h0'], 1 / 3)));
        $beta_h = 150 * (1 + pow(1.2 * $p['rh'] / 100, 18)) * ($p['h'] / $p['h0']) + 250;
    }

    $beta_fcm = null;
    if ($p['fcm'] === null) {
        $beta_fcm = null;
    } else if ($p['fcm'] <= 0) {
        $beta_fcm = null;
    } else {
        $beta_fcm = 5.3 / sqrt($p['fcm'] / 10);
    }

    $phi0 = null;
    if ($phi_rh === null) {
        $phi0 = null;
    } else if ($beta_fcm === null) {
        $phi0 = null;
    } else {
        $phi0 = $phi_rh * $beta_fcm * $beta_t0;
    }

    foreach ($rows as $index => $row) {
        $row['age'] = l3_age($row['reading']['reading_date'], $params['pour_date']);
        $loads = l3_loads($row['row_no'], $p);
        $creep_load = $loads[0];
        $elastic_load = $loads[1];
        $serial = l3_serial(l3_stress_for_serial($row['reading'], $history));
        $time = l3_time_modulus($serial, $p['creep_t0'], $eci);
        $row['beta_cc'] = $time[0];
        $row['beta_e'] = $time[1];
        $row['eci_t'] = $time[2];
        $row['phi_rh'] = $phi_rh;
        $row['beta_fcm'] = $beta_fcm;
        $row['beta_t0'] = $beta_t0;
        $row['phi0'] = $phi0;
        $row['beta_h'] = $beta_h;
        $row['beta_c'] = null;
        $row['phi'] = null;

        if ($row['age'] === null) {
            $row['beta_c'] = null;
        } else if (!l3_is_num($beta_h)) {
            $row['beta_c'] = null;
        } else {
            $dt = $row['age'] - $p['creep_t0'];
            $den = $beta_h + $dt;
            if ($den == 0) {
                $row['beta_c'] = null;
            } else if ($dt < 0) {
                $row['beta_c'] = null;
            } else if (($dt / $den) < 0) {
                $row['beta_c'] = null;
            } else {
                $row['beta_c'] = pow($dt / $den, 0.3);
                if ($phi0 === null) {
                    $row['phi'] = null;
                } else {
                    $row['phi'] = $phi0 * $row['beta_c'];
                }
            }
        }

        $row['creep'] = null;
        if (!l3_is_num($row['eci_t'])) {
            $row['creep'] = null;
        } else if ($row['eci_t'] == 0) {
            $row['creep'] = null;
        } else if (!l3_is_num($row['phi'])) {
            $row['creep'] = null;
        } else if (!l3_is_num($creep_load)) {
            $row['creep'] = null;
        } else {
            $row['creep'] = ($creep_load / $row['eci_t']) * $row['phi'] * 1e6;
        }

        if ($row['row_no'] == 1) {
            $row['elastic'] = 0;
        } else if ($p['eequ'] === null) {
            $row['elastic'] = null;
        } else if ($p['eequ'] == 0) {
            $row['elastic'] = null;
        } else if (!l3_is_num($elastic_load)) {
            $row['elastic'] = null;
        } else {
            $row['elastic'] = ($elastic_load / $p['eequ']) * 1000;
        }

        $rows[$index] = $row;
    }

    return $rows;
}

