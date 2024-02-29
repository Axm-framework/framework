<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class DateFormat

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class DateFormat
{
    /**
     * Validates a date string against a specified format.
     *
     * @param array $input An associative array containing the following keys:
     * - 'value'  (string): The date value to validate.
     * - 'format' (string): The expected format of the date.
     * @return bool True if the date is valid according to the format, false otherwise.
     */
    function validate($input)
    {
        $value  = $input['valueData'];
        $format = $input['format'];

        if (!is_string($value)) {
            return false;
        }

        $date = date_create_from_format($format, $value);

        return $date !== false;
    }

    public function message()
    {
        return 'The :field field is not a valid date in the format :format';
    }
}
