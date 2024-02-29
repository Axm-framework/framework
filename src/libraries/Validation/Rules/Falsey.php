<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class False

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Falsey
{
    /**
     * Validates if the input value is false or a falsey value.
     *
     * @param mixed $input The input value to validate.
     * @return bool True if the input value is false or a falsey value, false otherwise.
     */
    public function validate($input): bool
    {
        $input = $input['valueData'];
        return $input === false || $this->isFalsey($input);
    }

    /**
     * Checks if a value is falsey.
     *
     * @param mixed $value The value to check for falsey.
     * @return bool True if the value is falsey, false otherwise.
     */
    protected function isFalsey($value): bool
    {
        return !$value; // This checks if the value is false, null, 0, or an empty string.
    }

    public function message()
    {
        return 'The :field must be a falsey value.';
    }
}
