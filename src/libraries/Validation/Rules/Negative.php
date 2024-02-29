<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_numeric;

/*
* Class Negative

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Negative
{
    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_numeric($input)) {
            return false;
        }

        // Verifica si es un número negativo
        return $input < 0;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input must be a negative number.';
    }
}
