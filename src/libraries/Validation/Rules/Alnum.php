<?php

declare(strict_types=1);

namespace Validation\Rules;

use function ctype_alnum;

/*
* Class Alnum

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Alnum
{
  /**
   * Validates whether the input is alphanumeric.
   *
   * @param mixed $input The input to be validated.
   * @return bool True if the input is alphanumeric, false otherwise.
   */
  public function validate($input): bool
  {
    $value = $input['valueData'];

    if (!is_string($value)) {
      return false;
    }

    return ctype_alnum($value);
  }

  /**
   * Get the error message for validation failure.
   *
   * @return string The error message.
   */
  public function message()
  {
    return 'The input is not alphanumeric.';
  }
}
