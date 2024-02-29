<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class File

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class IsFile
{

    public function validate(string $rule, ?string $attribute): bool
    {
        if ($rule !== 'required' || empty($attribute) || !isset($_FILES[$attribute])) {
            return false;
        }

        return !empty($_FILES[$attribute]['tmp_name']);
    }
}
