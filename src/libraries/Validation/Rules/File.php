<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_file;
use function is_string;
use function is_readable;
use function is_writable;

/*
* Class File

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class File
{

    public function validate($input): bool
    {
        $input = $input['valueData'];

        return is_string($input) && is_file($input)
            && is_readable($input) && is_writable($input);
    }

    public function message()
    {
        return 'The value is not a File.';
    }
}
