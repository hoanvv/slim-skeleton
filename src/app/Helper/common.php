<?php

if (!function_exists('IS_DEBUG')) {
    function IS_DEBUG()
    {
        $bf_debug = $_ENV['BF_DEBUG'] ?? '0';
        return ($bf_debug == '1' || $bf_debug == 'true');
    }
}

if (!function_exists('IS_TESTING')) {
    function IS_TESTING()
    {
        $var = $_ENV['IS_TESTING'] ?? '0';
        return ($var == '1' || $var == 'true');
    }
}
