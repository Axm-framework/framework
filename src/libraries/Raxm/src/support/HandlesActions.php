<?php

namespace Raxm\Support;

use Axm;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

trait HandlesActions
{
    public function syncInput(string $name, string|array $value, $rehash = true)
    {
        $propertyName = $this->beforeFirstDot($name);

        if (($this->{$propertyName} instanceof Model || $this->{$propertyName} instanceof EloquentCollection) && $this->missingRuleFor($name)) {
            throw new Exception("Cannot bind to model data without validation rule: {$name}");
        }

        $this->callBeforeAndAfterSyncHooks($name, $value, function ($name, $value) use ($propertyName, $rehash) {
            if (!$this->propertyIsPublicAndNotDefinedOnBaseClass($propertyName)) {
                throw new Exception("Public property not found: {$propertyName}");
            }

            if ($this->containsDots($name)) {
                $nameParts = explode('.', $name);
                $keyName   = $this->afterFirstDot($name);
                $targetKey = $this->beforeFirstDot($keyName);

                $results = [];
                $results[$targetKey] = data_get($this->{$propertyName}, $targetKey, []);

                data_set($results, $keyName, $value);

                data_set($this->{$propertyName}, $targetKey, $results[$targetKey]);
            } else {
                $this->{$name} = $value;
            }

            $rehash && $this->id = $this->id ?? $this->generateId();
        });
    }

    protected function callBeforeAndAfterSyncHooks($name, $value, $callback)
    {
        $name = str_replace('_', '.', $name);
        $propertyName = ucfirst(explode('.', $name)[0]);
        $keyAfterFirstDot = null;
        $keyAfterLastDot  = null;

        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name);
            $keyAfterFirstDot = $parts[1];
            $keyAfterLastDot  = end($parts);
        }

        $beforeMethod = 'updating' . $propertyName;
        $afterMethod  = 'updated'  . $propertyName;

        $beforeNestedMethod = strpos($name, '.')
            ? 'updating' . str_replace('.', '_', $name)
            : false;

        $afterNestedMethod = strpos($name, '.')
            ? 'updated' . str_replace('.', '_', $name)
            : false;

        if (method_exists($this, 'updating')) {
            $this->updating($name, $value);
        }

        if (method_exists($this, $beforeMethod)) {
            $this->$beforeMethod($value, $keyAfterFirstDot);
        }

        if ($beforeNestedMethod && method_exists($this, $beforeNestedMethod)) {
            $this->$beforeNestedMethod($value, $keyAfterLastDot);
        }

        $callback($name, $value);

        if (method_exists($this, 'updated')) {
            $this->updated($name, $value);
        }

        if (method_exists($this, $afterMethod)) {
            $this->$afterMethod($value, $keyAfterFirstDot);
        }

        if ($afterNestedMethod && method_exists($this, $afterNestedMethod)) {
            $this->$afterNestedMethod($value, $keyAfterLastDot);
        }
    }


    public function callMethod(string $method, array $params = [], $captureReturnValueCallback = null)
    {
        $method = trim($method);
        $component = $this->component;
        $prop = array_shift($params);

        switch ($method) {
            case '$sync':
                $this->syncInput($prop, $params);
                return;

            case '$set':
                [$method, $params] = $prop;
                $this->syncInput($method, $params, $rehash = false);
                return;

            case '$toggle':
                $prop = array_shift($prop);
                if ($this->containsDots($prop)) {
                    $propertyName = $this->beforeFirstDot($prop);
                    $targetKey    = $this->afterFirstDot($prop);
                    $currentValue = data_get($this->{$propertyName}, $targetKey);
                } else {
                    $currentValue = $this->{$prop};
                }

                $this->syncInput($prop, !$currentValue, $rehash = false);
                return;

            case '$refresh':
                return;
        }

        if ($method === 'render') return;

        if (!method_exists($this, $method)) {
            if ($method === 'startUpload') {
                throw new RuntimeException(sprintf('Cannot handle file upload without 
                [Axm\Raxm\Suport\WithFileUploads] trait on the [ %s ] component class.', $component));
            }
        }

        if (!method_exists($this, $method)) {
            throw new RuntimeException(sprintf('Unable to call component method. Public method [ %s ]
             not found on component: [ %s ] ', $method, $component));
        }

        // Implementación para verificar método público y no definido en la clase base
        if (!$this->methodIsPublicAndNotDefinedOnBaseClass($method)) {
            throw new RuntimeException(sprintf('Unable to set component data. 
            Public method [ %s ] not found on component: [ %s ] ', $method, $component));
        }

        $returned = call_user_func_array([$this, $method], $params);

        if ($captureReturnValueCallback !== null) {
            $captureReturnValueCallback($returned);
        }
    }

    protected function methodIsPublicAndNotDefinedOnBaseClass($methodName)
    {
        $class = new ReflectionClass($this);
        $classMethods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($classMethods as $method) {
            if ($method->getName() === 'render') {
                continue;
            }

            if ($method->getDeclaringClass()->getName() !== self::class) {
                return true;
            }
        }

        return false;
    }
}
