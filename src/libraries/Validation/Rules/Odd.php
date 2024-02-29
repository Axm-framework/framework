<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class Odd

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Odd
{
    /**
     * Validate if the input is an odd integer.
     *
     * @param int $input The input value to validate.
     *
     * @return bool True if the input is an odd integer, false otherwise.
     */
    public function validate(int $input): bool
    {
        $input = $input['valueData'];

        return $input % 2 !== 0;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The provided value is not an odd integer.';
    }
}
