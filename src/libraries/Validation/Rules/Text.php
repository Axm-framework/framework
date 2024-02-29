<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_string;

/*
* Class Text

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Text
{
    public function validate($input): bool
    {
        $input = $input['valueData'];

        return is_string($input);
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input is not a valid string.';
    }
}
