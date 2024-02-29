<?php

declare(strict_types=1);

namespace Validation\Rules;

use function is_dir;
use function is_readable;
use function is_writable;
use function file_exists;

/*
* Class Directory

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Directory
{

    public function validate($input): bool
    {
        $input = $input['valueData'];
        return is_dir($input) && is_readable($input) && is_writable($input) && file_exists($input);
    }
}
