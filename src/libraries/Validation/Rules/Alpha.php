<?php

declare(strict_types=1);

namespace Validation\Rules;

use function ctype_alpha;

/*
* Class Alpha

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Alpha
{
    /**
     * Validates whether the input consists only of alphabetic characters.
     *
     * @param mixed $input The input to be validated.
     * @return bool True if the input consists only of alphabetic characters, false otherwise.
     */
    public function validate($input): bool
    {
        $value = $input['valueData'];

        if (!is_string($value)) {
            return false;
        }

        // Remove spaces and check if the remaining characters are alphabetic
        return ctype_alpha(str_replace(' ', '', $value));
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input does not consist only of alphabetic characters.';
    }
}
