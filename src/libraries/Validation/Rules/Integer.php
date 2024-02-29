<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_int;
use function is_numeric;
use function intval;

/*
* Class Integer

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Integer
{
    public function validate($input)
    {
        $input = $input['valueData'];

        if (!is_numeric($input)) {
            return false;
        }

        if (!is_int($input)) {
            return false;
        }

        if ($input < 0) {
            return false;
        }

        if (intval($input) !== $input) {
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'The :field must be an integer.';
    }
}
