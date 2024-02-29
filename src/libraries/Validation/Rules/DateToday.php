<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class DateToday

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class DateToday
{
    /**
     * Validate if a date is today a specified date.
     *
     * @param array $input An associative array containing:
     *  - 'valueData' (string): The date value to validate.
     *  - 'format' (string): The date to compare against.
     * @return bool True if the date is today the specified date, false otherwise.
     */
    public function validate(array $input): bool
    {
        $value = $input['valueData'];
        $today = date_create();

        if (!is_string($value)) {
            return false;
        }

        return ($value === $today);
    }

    /**
     * Get the validation error message.
     *
     * @return string The error message.
     */
    public function message(): string
    {
        return 'The value must be the current date.';
    }
}
