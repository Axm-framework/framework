<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class Same


 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Same
{
    /**
     * Validate if two fields have the same value.
     *
     * @param array $input An array containing field names and their values.
     * @return bool True if the fields have the same value, false otherwise.
     */
    public function validate($input): bool
    {
        // Get the names of the fields to compare
        $field1 = $input['field'];
        $field2 = $input['field2'];

        // Get the values of the fields
        $value1 = $input['value.' . $field1];
        $value2 = $input['value.' . $field2];

        return $value1 === $value2;
    }

    public function message()
    {
        return 'The :field must be identical to :field2.';
    }
}
