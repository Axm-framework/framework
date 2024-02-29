<?php

declare(strict_types=1);

namespace Validation\Rules;

use function array_pop;
use function array_sum;
use function is_numeric;
use function is_string;
use function mb_substr;
use function preg_match;
use function str_split;

/*
* Class Nif

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

final class Nif
{
    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_string($input)) {
            return false;
        }

        if (preg_match('/^(\d{8})([A-Z])$/', $input, $matches)) {
            return $this->validateDni((int) $matches[1], $matches[2]);
        }

        if (preg_match('/^([KLMXYZ])(\d{7})([A-Z])$/', $input, $matches)) {
            return $this->validateNie($matches[1], $matches[2], $matches[3]);
        }

        if (preg_match('/^([A-HJNP-SUVW])(\d{7})([0-9A-Z])$/', $input, $matches)) {
            return $this->validateCif($matches[2], $matches[3]);
        }

        return false;
    }

    private function validateDni(int $number, string $control): bool
    {
        $controlChars = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $expectedControl = $controlChars[$number % 23];
        return $expectedControl === $control;
    }

    private function validateNie(string $prefix, string $number, string $control): bool
    {
        $prefixToNumber = [
            'Y' => '1',
            'Z' => '2',
        ];

        $adjustedNumber = $prefix === 'Y' || $prefix === 'Z' ? $prefixToNumber[$prefix] . $number : $number;
        return $this->validateDni((int) $adjustedNumber, $control);
    }

    private function validateCif(string $number, string $control): bool
    {
        $code = 0;
        $position = 1;

        foreach (str_split($number) as $digit) {
            $increaser = $position % 2 !== 0 ? array_sum(str_split((string) ($digit * 2))) : $digit;
            $code += $increaser;
            ++$position;
        }

        $key = $code % 10 === 0 ? 0 : 10 - ($code % 10);

        if (is_numeric($control)) {
            return (int) $key === (int) $control;
        }

        $controlChars = 'JABCDEFGHI';
        $expectedControl = $controlChars[$key % 10];

        return $expectedControl === $control;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input is not a valid NIF, NIE, or CIF.';
    }
}
