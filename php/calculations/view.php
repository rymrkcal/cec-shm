<?php

function calc_view_only() {
    return !empty($GLOBALS['calc_readonly']);
}

function calc_view_begin() {
    if (calc_view_only()) {
        ob_start();
    }
}

function calc_view_end() {
    if (!calc_view_only()) {
        return;
    }

    $html = ob_get_clean();
    $html = preg_replace('/<form\b(?![^>]*\bonsubmit=)/i', '<form onsubmit="return false;"', $html);
    $html = preg_replace('/<input\b(?![^>]*\breadonly\b)/i', '<input readonly', $html);
    $html = preg_replace('/<select\b(?![^>]*\bdisabled\b)/i', '<select disabled', $html);
    echo $html;
}

