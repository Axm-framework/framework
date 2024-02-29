<?php

declare(strict_types=1);

namespace Validation\Rules;

use DateTimeImmutable;

use function is_scalar;

/*
* Class LeapDate

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class LeapDate
{
    public function validate($input, string $format = 'm-d'): bool
    {
        $input = $input['valueData'];

        if (!is_scalar($input)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat($format, (string) $input);

        return $date && $date->format('m-d') === '02-29';
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The date provided is not February 29.';
    }
}
