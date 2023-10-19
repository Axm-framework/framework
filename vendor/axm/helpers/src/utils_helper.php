<?php
if (!function_exists('postCodeCheck')) {

    function postCodeCheck($value, $country = 'ca')
    {
        $country_regex = array(
            'uk' => '/\\A\\b[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][ABD-HJLNP-UW-Z]{2}\\b\\z/i',
            'ca' => '/\\A\\b[ABCEGHJKLMNPRSTVXY][0-9][A-Z][ ]?[0-9][A-Z][0-9]\\b\\z/i',
            'it' => '/^[0-9]{5}$/i',
            'de' => '/^[0-9]{5}$/i',
            'be' => '/^[1-9]{1}[0-9]{3}$/i',
            'us' => '/\\A\\b[0-9]{5}(?:-[0-9]{4})?\\b\\z/i',
        );

        if (isset($country_regex[$country])) {
            return preg_match($country_regex[$country], $value);
        } else {
            return preg_match($country_regex['us'], $value);
        }
    }
}
