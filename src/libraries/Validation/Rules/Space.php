<?php

declare(strict_types=1);

namespace Respect\Validation\Rules;

/*
 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Space
{
    /**
     * Validate if a string contains only spaces.
     *
     * @param string $input The string to be validated.
     * @return bool True if the input contains only spaces, false otherwise.
     */
    public function containsOnlySpaces($input): bool
    {
        $input = $input['valueData'];

        // Utilizamos una expresión regular para verificar que la cadena contiene solo espacios en blanco.
        return preg_match('/^\s*$/', $input) === 1;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input does not contain only spaces.';
    }
}
