<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_string;
use function preg_quote;
use function preg_match;
use function strtolower;

/*
* Class Lower

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation
 */

class Lower
{
    public function validate($input, $allowChars = ''): bool
    {
        $input = $input['valueData'];

        if (!is_string($input)) {
            return false;
        }

        // Si se permiten caracteres adicionales, construir una expresión regular
        if (!empty($allowChars)) {
            $regex = '/^[a-z' . preg_quote($allowChars, '/') . ']+$/i'; // Usar 'i' para hacerlo insensible a mayúsculas/minúsculas
            return (bool) preg_match($regex, $input);
        }

        return $input === strtolower($input); // Convertir la cadena a minúsculas y comparar
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input must contain only lowercase characters.';
    }
}
