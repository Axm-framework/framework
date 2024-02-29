<?php

declare(strict_types=1);

namespace Validation\Rules;

use const FILTER_VALIDATE_URL;

/*
* Class Url

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Url
{
    private array $allowedSchemes = ['http', 'https', 'ftp'];

    /**
     * Validate if the input is a valid URL with an allowed scheme.
     *
     * @param string $input The URL to be validated.
     *
     * @return bool True if the input is a valid URL, false otherwise.
     */
    public function validate($input): bool
    {
        $input = $input['valueData'];

        $urlParts = parse_url($input);

        if (!isset($urlParts['scheme']) || !in_array($urlParts['scheme'], $this->allowedSchemes)) {
            return false;
        }

        return filter_var($input, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function getErrorMessage()
    {
        return 'The input is not a valid URL with an allowed scheme.';
    }
}
