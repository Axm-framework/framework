<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
 * Class Infinite
 *
 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package  Axm\Validation\Rules
 */

class Infinite
{
    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_numeric($input)) {
            return false;
        }

        return is_infinite((float) $input);
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input is not an infinite value.';
    }
}
