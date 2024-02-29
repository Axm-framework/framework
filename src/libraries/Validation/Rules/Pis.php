<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_scalar;
use function mb_strlen;
use function preg_match;
use function preg_replace;

/*
* Class Odd

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Pis
{

    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_scalar($input)) {
            return false;
        }

        $digits = (string) preg_replace('/\D/', '', (string) $input);
        if (mb_strlen($digits) != 11 || preg_match('/^' . $digits[0] . '{11}$/', $digits)) {
            return false;
        }

        $multipliers = [3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $summation = 0;
        for ($position = 0; $position < 10; ++$position) {
            $summation += (int) $digits[$position] * $multipliers[$position];
        }

        $checkDigit = (int) $digits[10];

        $modulo = $summation % 11;

        return $checkDigit === ($modulo < 2 ? 0 : 11 - $modulo);
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'Incorrect PIS.';
    }
}
