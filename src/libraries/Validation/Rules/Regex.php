<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_scalar;
use function preg_match;

/*
 * Class Regex.
 *
 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation
 */

class Regex
{
    /**
     * Validate the input against the specified regular expression pattern.
     *
     * @param mixed $input The value to be validated.
     * @param string $pattern The regular expression pattern to match against.
     * @return bool True if the input matches the pattern, false otherwise.
     */
    public function validate($input): bool
    {
        $value   = $input['valueData'];
        $pattern = $input['valueRule'];

        if (!is_scalar($value)) {
            return false;
        }

        if (!preg_match($pattern, (string) $value)) {
            return false;
        }

        return true;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input does not match the specified pattern.';
    }
}
