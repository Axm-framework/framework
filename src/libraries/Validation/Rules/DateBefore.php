<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class DateBefore

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class DateBefore
{
    /**
     * Validate if a date is before a specified date.
     *
     * @param array $input An associative array containing:
     *  - 'valueData' (string): The date value to validate.
     *  - 'format' (string): The date to compare against.
     * @return bool True if the date is before the specified date, false otherwise.
     */
    public function validate(array $input): bool
    {
        $value = $input['valueData'];
        $ruleValue = $input['format'];

        if (!is_string($value)) {
            return false;
        }

        $dateValue = date_create($value);
        $dateToCompare = date_create($ruleValue);

        return $dateValue < $dateToCompare;
    }

    /**
     * Get the validation error message.
     *
     * @return string The error message.
     */
    public function message(): string
    {
        return 'The value must be before the date :format.';
    }
}
