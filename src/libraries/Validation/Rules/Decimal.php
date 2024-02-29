<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_numeric;
use function is_float;
use function is_string;
use function preg_match;

/*
* Class Decimal

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Decimal
{

    /**
     * Check if a given input is a valid decimal number.
     *
     * @param mixed $input The input to validate.
     * @return bool True if the input is a valid decimal, false otherwise.
     */
    function validate($input)
    {
        $input = $input['valueData'];

        // Step 1: Check if the input is a string or a numeric input.
        if (!is_string($input) && !is_numeric($input) && !is_float($input)) {
            return false;
        }

        // Step 2: Use a regular expression to validate if the input is a valid decimal number.
        // - "^" indicates the start of the string.
        // - "-" is optional to allow negative numbers.
        // - "\\d*" allows zero or more digits before the decimal point.
        // - "\\." checks for the presence of the decimal point.
        // - "\\d+" requires at least one digit after the decimal point.
        // - "$" indicates the end of the string.
        $pattern = '/^-?\d*\.?\d+$/';

        // Step 3: Use the preg_match function to check if the input matches the regular expression.
        // It returns 1 if there's a match and 0 if there isn't.
        $matchResult = preg_match($pattern, $input);

        // Step 4: Check the result of preg_match.
        return $matchResult === 1;
    }

    public function message()
    {
        return ':inputRule is not a valid decimal number';
    }
}
