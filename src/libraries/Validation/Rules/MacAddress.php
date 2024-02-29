<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_string;
use function preg_match;

/*
* Class MacAddress

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class MacAddress
{
    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_string($input)) {
            return false;
        }

        // Expresión regular mejorada para validar direcciones MAC
        $macRegex = '/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/';

        return (bool)preg_match($macRegex, $input);
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input is not a valid MAC address.';
    }
}
