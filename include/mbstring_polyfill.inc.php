<?php
/**
 * Minimal polyfill for mbstring functions used by tFPDF.
 */

if (!function_exists('mb_strlen')) {
    function mb_strlen($str, $encoding = 'UTF-8') {
        if (strtolower($encoding) === 'utf-8') {
            return strlen(preg_replace('/[\x80-\xBF]/', '', $str));
        }
        return strlen($str);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = null, $encoding = 'UTF-8') {
        if (strtolower($encoding) === 'utf-8') {
            // Use preg_split to handle UTF-8 characters
            $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
            if ($length === null) {
                return implode('', array_slice($chars, $start));
            }
            return implode('', array_slice($chars, $start, $length));
        }
        return substr($str, $start, $length);
    }
}

if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding($str, $to_encoding, $from_encoding = null) {
        // Very basic implementation, might not handle all cases
        if (function_exists('iconv')) {
            return iconv($from_encoding ?? 'UTF-8', $to_encoding, $str);
        }
        return $str; // Fallback to original string
    }
}
?>
