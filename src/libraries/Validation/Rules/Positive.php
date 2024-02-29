<?php

declare(strict_types=1);

namespace Validation\Rules;

/*
* Class Positive

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation
 */

 class Positive
 {
     public function validate($input): bool
     {
         $value = $input['valueData'];
 
         if (!is_numeric($value) || $value <= 0) {
             return false;
         }
 
         return true;
     }
 
     /**
      * Get the error message for validation failure.
      *
      * @return string The error message.
      */
     public function message()
     {
         return 'The value must be a positive number.';
     }
 }
 