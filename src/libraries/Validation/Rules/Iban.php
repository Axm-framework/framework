<?php

declare(strict_types=1);

namespace Validation\Rules;

use function bcmod;
use function is_string;
use function ord;
use function preg_match;
use function preg_replace_callback;
use function str_replace;
use function strlen;
use function substr;

/**
 * Validates whether the input is a valid IBAN (International Bank Account Number) or not.
 *
 * The IBAN (International Bank Account Number) is an international standard for identifying bank accounts.
 * This class validates IBANs by checking their format, country code, and checksum.
 *
 * @author Mazen Touati <mazen_touati@hotmail.com>
 */
class Iban
{
    /**
     * An array of valid IBAN lengths for different countries.
     *
     * @var array
     */
    private const COUNTRIES_LENGTHS = [
        'AL' => 28,
        'AD' => 24,
        'AT' => 20,
        'AZ' => 28,
        'BH' => 22,
        'BE' => 16,
        'BA' => 20,
        'BR' => 29,
        'BG' => 22,
        'CR' => 21,
        'HR' => 21,
        'CY' => 28,
        'CZ' => 24,
        'DK' => 18,
        'DO' => 28,
        'EE' => 20,
        'FO' => 18,
        'FI' => 18,
        'FR' => 27,
        'GE' => 22,
        'DE' => 22,
        'GI' => 23,
        'GR' => 27,
        'GL' => 18,
        'GT' => 28,
        'HU' => 28,
        'IS' => 26,
        'IE' => 22,
        'IL' => 23,
        'IT' => 27,
        'JO' => 30,
        'KZ' => 20,
        'KW' => 30,
        'LV' => 21,
        'LB' => 28,
        'LI' => 21,
        'LT' => 20,
        'LU' => 20,
        'MK' => 19,
        'MT' => 31,
        'MR' => 27,
        'MU' => 30,
        'MD' => 24,
        'MC' => 27,
        'ME' => 22,
        'NL' => 18,
        'NO' => 15,
        'PK' => 24,
        'PL' => 28,
        'PS' => 29,
        'PT' => 25,
        'QA' => 29,
        'XK' => 20,
        'RO' => 24,
        'LC' => 32,
        'SM' => 27,
        'ST' => 25,
        'SA' => 24,
        'RS' => 22,
        'SC' => 31,
        'SK' => 24,
        'SI' => 19,
        'ES' => 24,
        'SE' => 24,
        'CH' => 21,
        'TL' => 23,
        'TN' => 24,
        'TR' => 26,
        'UA' => 29,
        'AE' => 23,
        'GB' => 22,
        'VG' => 24,
    ];

    /**
     * Validates if the input value is a valid IBAN.
     *
     * @param mixed $input The input value to validate.
     * @return bool True if the input value is a valid IBAN, false otherwise.
     */
    public function validate($input): bool
    {
        $input = $input['valueData'];

        // Ensure the input is a string.
        if (!is_string($input)) {
            return false;
        }

        // Remove spaces from the input and ensure it matches the IBAN pattern.
        $iban = str_replace(' ', '', $input);
        if (!preg_match('/[A-Z0-9]{15,34}/', $iban)) {
            return false;
        }

        // Extract the country code and check if it has a valid length.
        $countryCode = substr($iban, 0, 2);
        if (!$this->hasValidCountryLength($iban, $countryCode)) {
            return false;
        }

        // Extract the check digits, rearrange the IBAN, and calculate the checksum.
        $checkDigits = substr($iban, 2, 2);
        $bban = substr($iban, 4);
        $rearranged = $bban . $countryCode . $checkDigits;

        // Use bcmod to calculate the checksum and validate the IBAN.
        return bcmod($this->convertToInteger($rearranged), '97') === '1';
    }

    /**
     * Checks if the IBAN has a valid length for the given country code.
     *
     * @param string $iban The IBAN to check.
     * @param string $countryCode The country code extracted from the IBAN.
     * @return bool True if the IBAN has a valid length, false otherwise.
     */
    private function hasValidCountryLength(string $iban, string $countryCode): bool
    {
        if (!isset(self::COUNTRIES_LENGTHS[$countryCode])) {
            return false;
        }

        return strlen($iban) === self::COUNTRIES_LENGTHS[$countryCode];
    }

    /**
     * Converts characters in the rearranged IBAN to integers for checksum calculation.
     *
     * @param string $reArrangedIban The rearranged IBAN.
     * @return string The rearranged IBAN with characters converted to integers.
     */
    private function convertToInteger(string $reArrangedIban): string
    {
        return (string) preg_replace_callback(
            '/[A-Z]/',
            static function (array $match): int {
                return ord($match[0]) - 55;
            },
            $reArrangedIban
        );
    }

    /**
     * Returns the error message for an invalid IBAN.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The IBAN is not correct.';
    }
}
