<?php

declare(strict_types=1);

namespace Validation\Rules;

use function ceil;
use function is_numeric;
use function sqrt;


/*
* Class PrimeNumber

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation
 */

final class PrimeNumber
{

    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_numeric($input) || $input <= 1) {
            return false;
        }

        if ($input != 2 && ($input % 2) == 0) {
            return false;
        }

        for ($i = 3; $i <= ceil(sqrt((float) $input)); $i += 2) {
            if ($input % $i == 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The value must be a prime number.';
    }
}
