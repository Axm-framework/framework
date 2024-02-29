<?php

declare(strict_types=1);

namespace Validation\Rules;

use function preg_match;
use function sprintf;

/*
* Class Phone

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation
 */

class Phone
{
    private string $format = '/^\+?(%1$s)? ?(?(?=\()(\(%2$s\) ?%3$s)|([. -]?(%2$s[. -]*)?%3$s))$/';

    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_string($input)) {
            return false;
        }

        return preg_match($this->getPregFormat(), $input) > 0;
    }

    private function getPregFormat(): string
    {
        return sprintf(
            $this->format,
            '\d{0,3}',
            '\d{1,3}',
            '((\d{3,5})[. -]?(\d{4})|(\d{2}[. -]?){4})'
        );
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The provided value is not a valid phone number.';
    }
}
