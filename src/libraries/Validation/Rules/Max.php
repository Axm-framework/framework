<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_numeric;
use function is_string;
use function is_file;
use function floatval;
use function mb_strlen;
use function filesize;

/*
* Class Max

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Max
{

  public function validate($input): bool
  {
    $left  = $input['valueData'];
    $right = $input['valueRule'];

    if (empty($left)) return false;

    $isValid = false;

    if (is_numeric($left)) {
      $isValid = $this->validateNumeric($left, $right);
    } elseif (is_string($left)) {
      $isValid = $this->validateString($left, $right);
    } elseif (is_file($left)) {
      $isValid = $this->validateFileSize($left, $right);
    }

    if ($isValid) {
      return true;
    }

    return false;
  }

  private function validateNumeric($left, $right): bool
  {
    return $left <= floatval($right);
  }

  private function validateString($left, $right): bool
  {
    return  mb_strlen($left) <= floatval($right);
  }

  private function validateFileSize($left, $right): bool
  {
    $size    = filesize($left);
    $maxSize = $right;

    if ($size <= floatval($maxSize)) {
      return true;
    }

    return false;
  }

  public function message()
  {
    return 'Max length of the :field must be :valueRule.';
  }
}
