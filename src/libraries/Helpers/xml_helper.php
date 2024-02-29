<?php


if (!function_exists('xmlConvert')) {
    /**
     * Convert Reserved XML characters to Entities
     */
    function xmlConvert(string $str, bool $protectAll = false): string
    {
        $original = [
            '&',
            '<',
            '>',
            '"',
            "'",
            '-',
        ];

        $replacement = [
            '&amp;',
            '&lt;',
            '&gt;',
            '&quot;',
            '&apos;',
            '&#45;',
        ];

        // Replace entities and special characters
        $str = str_replace($original, $replacement, $str);

        // Protect all named entities if $protectAll is true
        if ($protectAll === true) {
            $str = htmlentities($str);
        }

        return $str;
    }
}
