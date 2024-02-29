<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class Equals

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Equals
{
    public function validate($value, $arguments): bool
    {
        $left  = $value;
        $rigth = $arguments;
        
        return $left == $rigth;
    }
}
