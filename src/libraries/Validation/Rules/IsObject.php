<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_object;

/*
 *Class IsObject

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
*/

class IsObject
{
    public function validate($input): bool
    {
        $input = $input['valueData'];
        return is_object($input);
    }

    public function message()
    {
        return 'The value must be an Object.';
    }
}
