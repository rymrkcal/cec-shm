<?php
require_once __DIR__ . '/common.php';
// Output tab formulas.

function l3_output($rows) {
    foreach ($rows as $index => $row) {
        if ($row['row_no'] == 1) {
            $row['out_measured'] = 0;
            $row['out_shrink'] = 0;
            $row['out_creep'] = 0;
            $row['out_strain'] = 0;
            $row['out_total'] = 0;
        } else {
            if ($row['measured'] === null) {
                $row['out_measured'] = null;
            } else {
                $row['out_measured'] = $row['measured'];
            }

            if (l3_is_num($row['eps_cs'])) {
                $row['out_shrink'] = $row['eps_cs'];
            } else {
                $row['out_shrink'] = null;
            }

            if (l3_is_num($row['creep'])) {
                $row['out_creep'] = $row['creep'];
            } else {
                $row['out_creep'] = null;
            }

            if (l3_is_num($row['elastic'])) {
                $row['out_strain'] = $row['elastic'];
            } else {
                $row['out_strain'] = null;
            }

            if (!l3_is_num($row['out_shrink'])) {
                $row['out_total'] = null;
            } else if (!l3_is_num($row['out_creep'])) {
                $row['out_total'] = null;
            } else if (!l3_is_num($row['out_strain'])) {
                $row['out_total'] = null;
            } else {
                $row['out_total'] = $row['out_shrink'] + $row['out_creep'] + $row['out_strain'];
            }
        }

        $rows[$index] = $row;
    }

    return $rows;
}

