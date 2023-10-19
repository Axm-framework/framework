<?php

if (!function_exists('numberToSize')) {
    /**
     * Formats a number as bytes, based on size, and adds the appropriate suffix.
     *
     * @param mixed  $num       Will be cast as int
     * @param int    $precision Number of decimal places (default: 1)
     * @param string $locale    Locale for language translation (default: null)
     * @return string|false Formatted number with suffix, or false if invalid input
     */
    function numberToSize($num, int $precision = 1, ?string $locale = null)
    {
        // Strip any formatting & ensure numeric input
        $num = filter_var($num, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        if (!is_numeric($num)) {
            return false;
        }
        $num = (float) $num;

        $units = [
            1099511627776 => lang('Number.terabyteAbbr', [], $locale),
            1073741824    => lang('Number.gigabyteAbbr', [], $locale),
            1048576       => lang('Number.megabyteAbbr', [], $locale),
            1024          => lang('Number.kilobyteAbbr', [], $locale),
            1             => lang('Number.bytes', [], $locale),
        ];

        foreach ($units as $factor => $unit) {
            if ($num >= $factor) {
                $num = round($num / $factor, $precision);
                return formatNumber($num, $precision, $locale, ['after' => ' ' . $unit]);
            }
        }

        return false;
    }
}

if (!function_exists('numberToAmount')) {
    /**
     * Converts numbers to a more readable representation when dealing with very large numbers.
     *
     * @param mixed  $num       Will be cast as int
     * @param int    $precision Number of decimal places (default: 0)
     * @param string $locale    Locale for language translation (default: null)
     * @return string|false Formatted number with suffix, or false if invalid input
     */
    function numberToAmount($num, int $precision = 0, ?string $locale = null)
    {
        // Strip any formatting & ensure numeric input
        $num = filter_var($num, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        if (!is_numeric($num)) {
            return false;
        }
        $num = (float) $num;

        $units = [
            1000000000000000 => lang('Number.quadrillion', [], $locale),
            1000000000000    => lang('Number.trillion', [], $locale),
            1000000000       => lang('Number.billion', [], $locale),
            1000000          => lang('Number.million', [], $locale),
            1000             => lang('Number.thousand', [], $locale),
            1                => '', // No suffix for numbers below 1000
        ];

        foreach ($units as $factor => $suffix) {
            if ($num >= $factor) {
                $num = round($num / $factor, $precision);
                return formatNumber($num, $precision, $locale, ['after' => $suffix]);
            }
        }

        return false;
    }
}

if (!function_exists('numberToCurrency')) {
    /**
     * Formats a number as currency based on the provided locale and currency code.
     *
     * @param float  $num       The number to format
     * @param string $currency  Currency code (e.g., USD)
     * @param string $locale    Locale for language translation (default: null)
     * @param int    $fraction  Number of decimal places (default: null)
     *
     * @return string Formatted currency string
     */
    function numberToCurrency(float $num, string $currency, ?string $locale = null, ?int $fraction = null): string
    {
        return formatNumber($num, 1, $locale, [
            'type'     => NumberFormatter::CURRENCY,
            'currency' => $currency,
            'fraction' => $fraction,
        ]);
    }
}

if (!function_exists('formatNumber')) {
    /**
     * A general-purpose, locale-aware number_format method.
     *
     * @param float  $num       The number to format
     * @param int    $precision Number of decimal places (default: 1)
     * @param string $locale    Locale for language translation (default: null)
     * @param array  $options   Additional options for formatting (default: [])
     * @return string Formatted number
     * @throws BadFunctionCallException if formatting fails
     */
    function formatNumber(float $num, int $precision = 1, ?string $locale = null, array $options = []): string
    {
        // Locale is either passed in here, negotiated with client, or grabbed from our config file.
        $locale = $locale ?? Axm::app()->request->getLocale();

        // Type can be any of the NumberFormatter options, but provide a default.
        $type = $options['type'] ?? NumberFormatter::DECIMAL;

        $formatter = new NumberFormatter($locale, $type);

        // Try to format it per the locale
        if ($type === NumberFormatter::CURRENCY) {
            $fraction = $options['fraction'] ?? null;
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $fraction);
            $output = $formatter->formatCurrency($num, $options['currency']);
        } else {
            // In order to specify a precision, we'll have to modify the pattern used by NumberFormatter.
            $pattern = '#,##0.' . str_repeat('#', $precision);

            $formatter->setPattern($pattern);
            $output = $formatter->format($num);
        }

        // This might lead a trailing period if $precision == 0
        $output = trim($output, '. ');

        if (intl_is_failure($formatter->getErrorCode())) {
            throw new BadFunctionCallException($formatter->getErrorMessage());
        }

        // Add any before/after text.
        if (isset($options['before']) && is_string($options['before'])) {
            $output = $options['before'] . $output;
        }

        if (isset($options['after']) && is_string($options['after'])) {
            $output .= $options['after'];
        }

        return $output;
    }
}

if (!function_exists('numberToRoman')) {
    /**
     * Convert a number to a Roman numeral.
     *
     * @param int|string $num The number to convert (must be between 1 and 3999)
     * @return string|null Roman numeral representation of the number, or null if out of range
     */
    function numberToRoman($num): ?string
    {
        $num = filter_var($num, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3999]]);

        if ($num === false) {
            return null;
        }

        $romanNumeral = '';

        $romanNumerals = [
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1,
        ];

        foreach ($romanNumerals as $roman => $arabic) {
            while ($num >= $arabic) {
                $romanNumeral .= $roman;
                $num -= $arabic;
            }
        }

        return $romanNumeral;
    }
}
