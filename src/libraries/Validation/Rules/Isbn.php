<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_scalar;
use function preg_match;

/**
 * Class Isbn
 *
 * Validates ISBN (International Standard Book Number) numbers.
 * @package Axm\Validation\Rules
 */
class Isbn
{
    private const ISBN_PATTERN = '/^(?:ISBN(?:-1[03])?:? )?(?=[0-9X]{10}$|(?=(?:[0-9]+[- ]){3})[- 0-9X]{13}$|97[89][0-9]{10}$|(?=(?:[0-9]+[- ]){4})[- 0-9]{17}$)(?:97[89][- ]?)?[0-9]{1,5}[- ]?[0-9]+[- ]?[0-9]+[- ]?[0-9X]$/';

    /**
     * Validates an ISBN number.
     *
     * @param mixed $input The input value to validate.
     * @return bool True if the input is a valid ISBN number, false otherwise.
     */
    public function validate($input): bool
    {
        $input = $input['valueData'];

        if (!is_scalar($input)) {
            // Input is not a scalar value, so it cannot be a valid ISBN.
            return false;
        }

        return $this->isValidIsbn((string) $input);
    }

    /**
     * Check if a given string is a valid ISBN number.
     *
     * @param string $input The input string to check.
     * @return bool True if the input is a valid ISBN number, false otherwise.
     */
    private function isValidIsbn(string $input): bool
    {
        return preg_match(self::ISBN_PATTERN, $input) > 0;
    }

    /**
     * Get the error message for ISBN validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The ISBN value is not correct.';
    }
}
