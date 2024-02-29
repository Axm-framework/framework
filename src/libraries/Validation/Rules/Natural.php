<?php

declare(strict_types=1);

namespace Validation\Rules;

use function floor;
use function is_numeric;

/*
* Class Natural

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Natural
{
    public function validate($input): bool
    {
        $input = $input['valueData'];

        // Verifica si es un número positivo y entero
        return is_numeric($input) && $input >= 0 && intval($input) == $input;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input must be a non-negative integer.';
    }
}
