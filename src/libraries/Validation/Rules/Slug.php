<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_string;
use function mb_strstr;
use function preg_match;

/*
* Class Infinite

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation
 */

class Slug
{
    /**
     * Validate if a string is a valid slug.
     *
     * @param string $slug The string to be validated.
     *
     * @return bool True if the input is a valid slug, false otherwise.
     */
    public function validate($input): bool
    {
        $slug = $input['valueData'];

        if (!is_string($slug)) {
            return false;
        }

        // Regular expression for validating slugs: lowercase alphanumeric words separated by hyphens.
        $pattern = '/^([a-z0-9]+-)*[a-z0-9]+$/';

        if (preg_match($pattern, $slug) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Get the error message for validation failure.
     *
     * @return string The error message.
     */
    public function message()
    {
        return 'The input is not a valid slug.';
    }
}
