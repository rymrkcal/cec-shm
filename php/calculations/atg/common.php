<?php
// Shared ATG helpers. No formulas and no page HTML.

function atg_clean($value, $max) {
    $value = trim((string) $value);
    if (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function atg_num($value) {
    $value = str_replace(',', '', trim((string) $value));
    if ($value === '' || !is_numeric($value)) {
        return null;
    }
    return (float) $value;
}

function atg_db_number($value, $whole) {
    $value = str_replace(',', '', trim((string) $value));
    if ($value === '' || !is_numeric($value)) {
        return null;
    }
    if ($whole && !preg_match('/^-?\d+$/', $value)) {
        return null;
    }
    return $value;
}

function atg_stored($value) {
    if ($value === null) {
        return '';
    }
    if (is_float($value)) {
        if (!is_finite($value)) {
            return '';
        }
        $text = rtrim(rtrim(sprintf('%.8F', $value), '0'), '.');
        if ($text === '' || $text === '-0') {
            return '0';
        }
        return $text;
    }
    return (string) $value;
}

function atg_is_num($value) {
    if (is_int($value) || is_float($value)) {
        return is_finite((float) $value);
    }
    return false;
}

function atg_date_int($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return 0;
    }

    if (preg_match('/^\d{8}$/', $value)) {
        $packed = (int) $value;
        $year = intdiv($packed, 10000);
        $month = intdiv($packed, 100) % 100;
        $day = $packed % 100;
        if (checkdate($month, $day, $year)) {
            return $packed;
        }
    }

    $months = array('jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12);
    if (preg_match('/^(\d{1,2})\D*(\d{1,2}|[a-z]{3})\D*(\d{4})$/i', $value, $match)) {
        $day = (int) $match[1];
        $year = (int) $match[3];
        $month = ctype_digit($match[2]) ? (int) $match[2] : ($months[strtolower($match[2])] ?? 0);
        if (checkdate($month, $day, $year)) {
            return $year * 10000 + $month * 100 + $day;
        }
    }

    $formats = array('d-m-Y', 'd/m/Y', 'Y-m-d', 'd-m-y', 'd/m/y');
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat('!' . $format, $value, new DateTimeZone('UTC'));
        $errors = DateTime::getLastErrors();
        $bad = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
        if ($date instanceof DateTime && !$bad) {
            return (int) $date->format('Ymd');
        }
    }

    return 0;
}

function atg_store_date($value) {
    return atg_date_int($value);
}

function atg_date_text($value) {
    $names = array(1 => 'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec');
    $n = (int) $value;
    $year = intdiv($n, 10000);
    $month = intdiv($n, 100) % 100;
    $day = $n % 100;
    if (!isset($names[$month]) || !checkdate($month, $day, $year)) {
        $n = atg_date_int($value);
        $year = intdiv($n, 10000);
        $month = intdiv($n, 100) % 100;
        $day = $n % 100;
    }
    if (!isset($names[$month]) || !checkdate($month, $day, $year)) {
        return '';
    }
    return $day . ' ' . $names[$month] . ' ' . $year;
}

function atg_serial($value) {
    $packed = atg_date_int($value);
    if ($packed <= 0) {
        return null;
    }

    $value = sprintf('%02d/%02d/%04d', $packed % 100, intdiv($packed, 100) % 100, intdiv($packed, 10000));
    $date = DateTime::createFromFormat('!d/m/Y', $value, new DateTimeZone('UTC'));
    if (!$date instanceof DateTime) {
        return null;
    }
    return (float) (int) round($date->getTimestamp() / 86400 + 25569);
}

function atg_age($measured, $pour) {
    $measured_serial = atg_serial($measured);
    $pour_serial = atg_serial($pour);
    if ($measured_serial === null) {
        return null;
    }
    if ($pour_serial === null) {
        return null;
    }
    return (float) (int) round($measured_serial - $pour_serial);
}

function atg_avg($left, $right) {
    if ($left === null) {
        if ($right === null) {
            return null;
        }
        return $right;
    }
    if ($right === null) {
        return $left;
    }
    return ($left + $right) / 2;
}

function atg_blank_reading($row_no) {
    return array(
        'row_no' => $row_no,
        'item_name' => '',
        'reading_date' => '',
        'bottom_strain' => '',
        'bottom_temp' => '',
        'top_strain' => '',
        'top_temp' => '',
        'stress_date' => '',
    );
}

function atg_default_params() {
    return array(
        'concrete_grade' => '',
        'sfc' => '',
        'pour_date' => '',
        'rh' => '',
        'ac' => '',
        'ar' => '',
        'u' => '',
        'load_value' => '',
        'serial_no' => '',
        'ks' => '',
        'ktt' => '',
        'kts' => '',
        'kst' => '',
        'temp_fbg_1' => '',
        'temp_fbg_2' => '',
        'base_floor' => '',
        'mid_floor' => '',
        'roof_floor' => '',
        'creep_t0' => '28',
        'ts' => '0',
        'h0' => '100',
    );
}

function atg_used($reading) {
    $fields = array('item_name', 'reading_date', 'bottom_strain', 'bottom_temp', 'top_strain', 'top_temp', 'stress_date');
    foreach ($fields as $field) {
        $value = trim((string) ($reading[$field] ?? ''));
        if ($field === 'reading_date' || $field === 'stress_date') {
            if ($value === '' || $value === '0') {
                continue;
            }
        }
        if ($value !== '') {
            return true;
        }
    }
    return false;
}

function atg_stress_for_serial($reading, $history) {
    $stress_date = trim((string) ($reading['stress_date'] ?? ''));
    if ($stress_date !== '' && $stress_date !== '0') {
        return $stress_date;
    }
    foreach ($history as $item) {
        if ((int) $item['row_no'] === (int) $reading['row_no']) {
            return $item['history_date'];
        }
    }
    return '';
}

function atg_floor_date($history, $floor) {
    $row_no = (int) $floor;
    if ($row_no < 1) {
        return null;
    }
    foreach ($history as $item) {
        if ((int) $item['row_no'] === $row_no) {
            $date = trim((string) $item['history_date']);
            if ($date === '') {
                return null;
            }
            return $date;
        }
    }
    return null;
}

function atg_text($value, $decimals = null) {
    if ($value === null || $value === '') {
        return '';
    }
    if (!atg_is_num($value)) {
        return '';
    }
    if ($decimals === null) {
        $rounded = round((float) $value, 6);
        if (abs($rounded) < 0.0000005) {
            return '0';
        }
        $text = number_format($rounded, 6, '.', '');
        return rtrim(rtrim($text, '0'), '.');
    }
    $places = (int) $decimals;
    return number_format(round((float) $value, $places), $places, '.', '');
}

function atg_show($used, $value) {
    if (!atg_is_num($value)) {
        return null;
    }
    return $value;
}

