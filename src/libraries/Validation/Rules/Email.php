<?php

declare(strict_types=1);

namespace Validation\Rules;

use function filter_var;
use function is_string;
use function strlen;

use const FILTER_VALIDATE_EMAIL;

/*
* Class Email

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Email
{

    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_string($input) || strlen($input) > 320) {
            return false;
        }

        return (bool) filter_var($input, FILTER_VALIDATE_EMAIL);
    }


    public function message()
    {
        return 'The :field must be a valid format.';
    }
}
