<?php

declare(strict_types=1);

namespace Validation\Rules;

use function floatval;
use function in_array;
use InvalidArgumentException;

/*
* Class Compare

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Compare
{

    public function validate($input): bool
    {
        $left     = floatval($input['valueData']);
        $right    = floatval($input['valueRule']);
        $operator = $input['operator'];

        if (!in_array($operator, ['>', '<', '>=', '<=', '==', '===', '!=', '!=='])) {
            throw new InvalidArgumentException('Invalid operator');
        }

        switch ($operator) {
            case '>':
                return $left > $right;
            case '<':
                return $left < $right;
            case '>=':
                return $left >= $right;
            case '<=':
                return $left <= $right;
            case '==':
                return $left == $right;
            case '===':
                return $left === $right;
            case '!=':
                return $left != $right;
            case '!==':
                return $left !== $right;
        }
    }

    public function message()
    {
        return 'The :field must be :operator a :valueRule.';
    }
}
