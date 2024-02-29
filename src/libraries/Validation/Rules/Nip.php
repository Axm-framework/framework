<?php

declare(strict_types=1);

namespace Validation\Rules;

use function array_map;
use function is_scalar;
use function preg_match;
use function str_split;

/*
* Class Nip

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

/**
 * A class for validating National Identification Numbers (NIP).
 */
final class Nip
{
    /**
     * Validate the NIP.
     *
     * @param mixed $input The input value to validate.
     *
     * @return bool True if the input is a valid NIP, false otherwise.
     */
    public function validate($input): bool
    {
        $input = $input['valueData'];
        
        // Ensure the input is scalar.
        if (!is_scalar($input)) {
            return false;
        }

        $value = (string) $input;

        // Use regular expression to check if it matches the NIP pattern.
        if (!preg_match('/^\d{10}$/', $value)) {
            return false;
        }

        // Weight coefficients for NIP validation.
        $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
        $digits = array_map('intval', str_split($value));

        $targetControlNumber = $digits[9];
        $calculatedControlNumber = $this->calculateControlNumber($digits, $weights);

        // Compare the calculated control number with the target control number.
        return $targetControlNumber === $calculatedControlNumber;
    }

    /**
     * Calculate the control number for NIP validation.
     *
     * @param array $digits  An array of NIP digits.
     * @param array $weights An array of weight coefficients.
     *
     * @return int The calculated control number.
     */
    private function calculateControlNumber(array $digits, array $weights): int
    {
        $controlNumber = 0;

        for ($i = 0; $i < 9; ++$i) {
            $controlNumber += $digits[$i] * $weights[$i];
        }

        return $controlNumber % 11;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input is not a valid NIP.';
    }
}
