<?php

declare(strict_types=1);

namespace Validation\Rules;

use function preg_match;

/*
* Class Consonant

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Consonant
{
  /**
   * Validate whether a value contains only consonants or whitespace.
   *
   * @param string $value The value to validate.
   * @return bool True if the value meets the conditions, false otherwise.
   */
  public function validate($input): bool
  {
    $input = $input['valueData'];

    // Regular expressions for consonants and whitespace.
    $consonantsRegex = '/^[b-df-hj-np-tv-z]+$/i';
    $whitespaceRegex = '/^\s*$/';

    // Check if the value contains only consonants or whitespace.
    return preg_match($consonantsRegex, $input) || preg_match($whitespaceRegex, $input);
  }

  public function message()
  {
    return 'The :field must contain only consonants.';
  }
}
