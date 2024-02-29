<?php

declare(strict_types=1);

namespace Validation\Rules;

use ReflectionFunction;

/*
* Class CustomRule

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class CustomRule
{
    private $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function validate($input): bool
    {
        $value = $input['valueData'];

        $reflection = new ReflectionFunction($this->callback);
        $parameters = $reflection->getParameters();


        if (is_callable($this->callback)) {
            // Call the custom callback for validation.
            return call_user_func($this->callback, $value, $parameters);
        }

        return false;
    }

    public function message()
    {
        return 'The :field value is incorrect.';
    }
}
