<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_numeric;

/*
* Class Fibonacci

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

final class Fibonacci
{
    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_numeric($input) || $input < 0) {
            return false;
        }

        $a = 0;
        $b = 1;

        while ($b < $input) {
            $temp = $a + $b;
            $a = $b;
            $b = $temp;
        }

        return $b === $input || $input === 0;
    }

    public function message()
    {
        return 'The value is not a Fibonacci number.';
    }
}
