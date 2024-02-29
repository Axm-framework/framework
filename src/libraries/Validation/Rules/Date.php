<?php

declare(strict_types=1);

namespace Validation\Rules;

use DateTime;
use function is_scalar;

/*
* Class Date

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Date
{

    public function validate($input): bool
    {
        if (!is_scalar($input)) {
            return false;
        }

        try {
            $this->isValidDateTime($input);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isValidDateTime($date, $format = null)
    {
        if ($format === null) {
            $dateTime = new DateTime($date);
        } else {
            $dateTime = DateTime::createFromFormat($format, $date);
        }

        if ($dateTime === false) {
            throw new \Exception('Invalid date/time format');
        }

        return true;
    }
}
