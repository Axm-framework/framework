<?php

declare(strict_types=1);

namespace Validation\Rules;

use InvalidArgumentException;
use Model;

/*
* Class EmailValidator

 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Unique
{
    public function validate($parameters): bool
    {
        $tableName   = $parameters['tableName'];
        $tableFields = $parameters['tableFields'];
        $value = $parameters['valueData'];

        $result = $this->searchValueInTable($tableName, $tableFields, $value);

        return !$result;
    }

    function searchValueInTable(string $tableName, array $tableFields, $value)
    {
        $modelClass = $this->getModelClass($tableName);

        return $modelClass::where(function ($query) use ($tableFields, $value) {
            if (is_array($tableFields)) {
                foreach ($tableFields as $field) {
                    $query->orWhere($field, $value);
                }
            }
        })->exists();
    }

    private function getModelClass(string $tableName)
    {
        $modelClass = 'App\\Models\\' . ucfirst($tableName);

        if (!is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException('Invalid model class: ' . $modelClass);
        }

        return $modelClass;
    }

    public function message()
    {
        return 'This :field already exists';
    }
}
