<?php

declare(strict_types=1);

namespace Validation\Rules;

use InvalidArgumentException;

class Max
{
  public function validate($input): bool
  {
    $left = $input['valueData'];
    $right = $input['valueRule'];

    if ($this->isEmpty($left)) {
      return false;
    }

    return match (true) {
      is_numeric($left) => $left <= $right,
      is_string($left) => mb_strlen($left) <= $right,
      is_file($left['tmp_name']) => $left['size'] <= $right,

      default => throw new InvalidArgumentException('Invalid input type'),
    };
  }

  private function isEmpty($value): bool
  {
    return $value === null || $value === '' || $value === [];
  }

  public function message(): string
  {
    return 'Max length of the :field must be :valueRule.';
  }
}
