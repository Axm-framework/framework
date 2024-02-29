<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_scalar;
use function is_array;
use function trim;
use function count;

/*
* Class Required

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Required
{
    public final function validate($input): bool
    {
        $value = $input['valueData'];

        if (is_scalar($value)) {
            return !is_null($value) && trim($value) !== '';
        } elseif (is_array($value)) {
            return count($value) > 0;
        }

        return false;
    }

    public function message()
    {
        return 'The :field is required.';
    }
}
