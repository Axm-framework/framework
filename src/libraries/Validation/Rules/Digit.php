<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class Digit

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Digit
{

    public function validate($input): bool
    {
        $input = $input['valueData'];
        return preg_match('/^-?\d+(\.\d+)?$/', $input) === 1;
    }

    public function message()
    {
        return ':valueRule is not a valid digit';
    }
}
