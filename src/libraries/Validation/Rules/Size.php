<?php

declare(strict_types=1);

namespace Validation\Rules;

use SplFileInfo;
use function is_string;
use function is_array;
use function strlen;

/*
* Class Size

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Size
{
    /**
     * Validate the size of a value against an expected size.
     *
     * @param mixed $value      The value to validate.
     * @param mixed $expectedSize The expected size to compare against.
     * @return bool True if the value matches the expected size, false otherwise.
     */
    public function validate($input): bool
    {

        $value  = $input['valueData'];
        $expectedSize = $input['valueRule'];

        if ($value instanceof SplFileInfo) {
            return $value->getSize() == $expectedSize;
        }

        if (is_array($value) || is_string($value)) {
            return strlen($value) == $expectedSize;
        }

        return $value == $expectedSize;
    }

    public function message()
    {
        return 'The file size cannot exceed :valueRule.';
    }
}
