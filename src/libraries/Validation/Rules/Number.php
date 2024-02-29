<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_numeric;

/*
* Class Number

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation
 */

class Number
{
    public function validate($input): bool
    {
        $input = $input['valueData'];

        return is_numeric($input);
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input should be a valid number.';
    }
}
