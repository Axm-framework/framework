<?php

declare(strict_types=1);

namespace Validation\Rules;


use DateTimeInterface;

use function date;
use function is_numeric;
use function is_scalar;
use function sprintf;
use function strtotime;

/*
* Class LeapYear

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class LeapYear
{
    public function validate($input): bool
    {
        $input = $input['valueData'];

        $year = $this->extractYear($input);

        if ($year === null) {
            return false;
        }

        return $this->isLeapYear($year);
    }

    private function extractYear($input): ?int
    {
        if (is_numeric($input)) {
            return (int) $input;
        } elseif (is_scalar($input)) {
            $year = (int) date('Y', strtotime((string) $input));
            if ($year !== false) {
                return $year;
            }
        } elseif ($input instanceof DateTimeInterface) {
            return (int) $input->format('Y');
        }

        return null;
    }

    private function isLeapYear(int $year): bool
    {
        return (bool) date('L', strtotime("$year-02-29"));
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The provided year is not a leap year.';
    }
}
