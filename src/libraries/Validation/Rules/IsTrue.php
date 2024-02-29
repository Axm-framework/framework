<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
 * Class IsTrue
 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class IsTrue
{

    function validate($input): bool
    {
        $input = $input['valueData'];
        return filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
    }

    public function message()
    {
        return 'Value must be a True.';
    }
}
