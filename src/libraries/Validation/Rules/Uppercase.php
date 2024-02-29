<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_string;
use function ctype_upper;

/*
* Class Uppercase

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation
 */

class Uppercase
{

    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_string($input)) {
            return false;
        }

        return  ctype_upper($input);
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function getErrorMessage()
    {
        return 'The input does not consist of uppercase letters only.';
    }
}
