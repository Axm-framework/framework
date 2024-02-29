<?php

declare(strict_types=1);

namespace Respect\Validation\Rules;

use function is_string;
use function json_decode;
use function json_last_error;

use const JSON_ERROR_NONE;

/*
 * This file is part of Respect/Validation.
 * 
 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Json
{
    /**
     * Validate if the value is a valid JSON string.
     *
     * @param mixed $input The input value to validate.
     * @return bool True if the value is a valid JSON string; otherwise, false.
     */
    public function validate($input): bool
    {
        $input = $input['valueData'];

        // Ensure the input is a string and not empty.
        if (!is_string($input) || $input === '') {
            return false;
        }

        // Attempt to decode the JSON string.
        $decoded = json_decode($input);

        // Check if decoding was successful and there were no JSON errors.
        return $decoded !== null && json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The value must be a valid JSON string.';
    }
}
