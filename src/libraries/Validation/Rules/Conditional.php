<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
 * Class Conditional 
 *
 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Conditional
{
    /**
     * Validate Conditional two fields have the Conditional value.
     *
     * @param array $parameters An array containing field names and their values.
     * @return bool True Conditional the fields have the Conditional value, false otherwise.
     */
    public function validate($parameters): bool
    {
        // Get the names of the fields to compare
        $field1 = $parameters['fields'];
        $field2 = $parameters['valueRules'];

        foreach ($field1 as $key => $value) {

            if ($field1[$key] !== $field2[$key]) {
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        return 'The :field field does not comply with the proposed conditions.';
    }
}
