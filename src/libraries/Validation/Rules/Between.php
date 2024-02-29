<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class Between


 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Between
{
    /**
     * Validate the input value against the 'between' rule.
     *
     * @param array $input An associative array containing the input data.
     * @return bool True if the value is between the specified range, false otherwise.
     */
    public function validate($input): bool
    {
        $value = $input['valueData'];
        $min = $input['min'];
        $max = $input['max'];

        if ($min > $max) {
            list($min, $max) = [$max, $min]; // Intercambia los valores
        }

        return $value >= $min && $value <= $max;
    }

    public function message()
    {
        return 'The :field must be between :min and :max inclusive.';
    }
}
