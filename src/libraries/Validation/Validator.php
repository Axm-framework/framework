<?php

declare(strict_types=1);

namespace Validation;

use RuntimeException;

/*
 * (c) Juan Cristobal <juancristobalgd1@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\Validation\Rules
 */

class Validator
{
    private static $instance;
    private array $instanceClassValidation = [];
    private string $separator = '|';
    private string $not = '!';
    private array $comparisonOperators = ['<=', '>=', '===', '==', '!=', '!==', '<', '>'];
    private string $currentOperator;
    protected array $rules = [];
    protected array $data = [];
    protected array $errors = [];
    protected array $countErrors = [];
    protected array $occurrencesErrors = [];
    public bool $skipValidation = false;
    protected array $validationRules = [];
    public bool $skipErrorMessage = false;
    protected $errorMessage;
    private $matchesRulesComparison = [];
    private static $customCallback;


    /**
     * Reset the error count for a specific validation rule.
     */
    public function resetCountError(string $rule)
    {
        unset($this->occurrencesErrors[$rule]);
    }

    /**
     * Reset the error count for specific validation rules.
     */
    public function resetCountErrors(array $rules)
    {
        foreach ($rules as $rule) {
            // Unset the error count for the specified rule.
            unset($this->occurrencesErrors[$rule]);
        }
    }

    /**
     * Add an error for a specific field and rule.
     */
    public function addError(string $field, string $rule, string $message = '')
    {
        $this->errors[$field][] = [
            'rule' => $rule,
            'message' => $message ?: $this->errorMessage[$rule]
        ];

        $this->rules[] = $rule;
    }

    /**
     * Remove a validation error message for a specific field.
     */
    public function removeValidation(?string $field = null): bool
    {
        if (isset($this->errors[$field])) {
            unset($this->errors[$field]);
            return true;
        }

        return false;
    }


    /**
     * Set the validation rules.
     */
    public function setRules(array $rules): bool
    {
        // Check if the provided data is empty.
        if (empty($rules))
            return false;

        $this->reset();
        $this->validationRules = $rules;

        return true;
    }

    /**
     * Set the data to be validated.
     */
    public function setData(array $data): bool
    {
        // Check if the provided data is empty.
        if (empty($data))
            return false;

        $this->data = $data;
        return true;
    }

    /**
     * Add additional validation rules to the existing rules.
     */
    public function addRules(array $rules): self
    {
        // Merges the provided rules with the existing rules.
        $this->validationRules = array_replace_recursive($this->validationRules, $rules);

        return $this;
    }

    /**
     * Get the validation rules defined for this validator.
     */
    public function getRules(): array
    {
        return $this->validationRules;
    }

    /**
     * Add an error message for a specific field and validation rule.
     */
    public function setError(string $field, string $rule, string $message): void
    {
        $this->errors[$field] = [];
        $addError = [
            'rule' => $rule,
            'message' => $message,
        ];

        // Add the error entry to the field's error array.
        $this->errors[$field][] = $addError;
    }

    /**
     * Check if there are errors associated with a specific field.
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get the first error message for a specific field.
     */
    public function getFirstErrorByField(string $field): string
    {
        $errors = $this->errors[$field] ?? [];

        if (!empty($errors)) {
            return $errors[0]['message'];
        }

        return '';
    }

    /**
     * Get all error messages as an array.
     */
    public function getErrors(): array
    {
        $errorMessages = [];
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                if (isset($error['message'])) {
                    $errorMessages[] = $error['message'];
                }
            }
        }

        return $errorMessages;
    }

    /**
     * Get the first error message from the list of errors.
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? '';
    }

    /**
     * Resets the class to a blank slate. Should be called whenever
     * you need to process more than one array.  */
    private function reset(bool $resetValidationRules = true)
    {
        $this->rules = [];
        $this->errors = [];

        if ($resetValidationRules) {
            $this->validationRules = [];
        }

        return $this;
    }

    /**
     * Create a new validator instance and configure it with provided rules and data.
     */
    public static function make(array $rules, array $data = [], callable $customCallback = null): self
    {
        static::$customCallback = $customCallback;

        $validator = static::getInstance();
        $validator->setRules($rules);
        $validator->setData($data);

        return $validator;
    }

    /**
     *  class Singleton 
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Validates the data.
     */
    public function validate(): bool
    {
        // If validation is set to be skipped, return true.
        if ($this->skipValidation) {
            return true;
        }

        return $this->startValidation();
    }

    /**
     * Check if the validation process has failed.
     */
    public function fails(): bool
    {
        return (false === !empty($this->errors));
    }

    /**
     * Validate a set of data using defined validation rules.
     */
    private function startValidation(): bool
    {
        $this->reset(false); // Set the data to validate and reset errors.

        foreach ($this->validationRules as $field => $rules) {
            $count = strlen($rules);

            if (array_key_exists($field, $this->data)) { // Check if the field is present in the data and process the validation rules.
                $rules = !is_array($rules) ? $this->parseValidationRules($rules) : $rules; // Convert the rules to an array if they are not already converted to an array.

                foreach ($rules as $i => $ruleItem) {
                    if (is_string($ruleItem)) {
                        $this->applyValidationRules($ruleItem, $field, $i, $count);
                    } elseif (is_object($ruleItem)) {
                        $this->applyCustomValidation($ruleItem, $field);
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Parse a validation rules string into an array.
     */
    private function parseValidationRules(string $rulesString): array
    {
        // Split the rules string by '|' and remove any empty rules.
        return array_filter(explode($this->separator, $rulesString));
    }

    /**
     * Apply a custom validation rule to a specific field.
     */
    private function applyCustomValidation($ruleItem, string $field)
    {
        if ($ruleItem instanceof \Validation\Rules\CustomRule) {
            // Prepare the parameters before executing the custom validation rule.
            $updatedParameters = $this->prepareParametersBeforeExecution('custom_rule', $field, []);
            $this->executeRule($updatedParameters);
        }
    }

    /**
     * Apply validation rules to a field based on the specified rule item.
     */
    private function applyValidationRules(string $ruleItem, string $field, int $i, int $count)
    {
        $parameters = [];

        $parameters[] = match (true) {
            strpos($ruleItem, ':') !== false => $this->parseRuleWithEqualSign($ruleItem, $field),
            $this->isComparisonRule($ruleItem) => $this->compileComparisonRule(),
            default => [],
        };

        // Continue only if there are more rules to apply.
        if ($i + 1 > $count) {
            return;
        }

        $updatedParameters = $this->prepareParametersBeforeExecution($ruleItem, $field, $parameters);

        // Execute the validation rule.
        $this->executeRule($updatedParameters);
    }


    /**
     * Prepare validation parameters before execution.
     */
    private function prepareParametersBeforeExecution(string $rule, string $field, array $parameters): array
    {
        unset($parameters[0]['rule']);  // Remove the 'rule' key from the array to avoid duplicates

        // Merge the remaining data from $parameters into the final array
        $updatedParameters = array_merge([
            'rule' => camelCase($parameters[0]['rule'] ?? $parameters['rule'] ?? $rule),
            'field' => $field,
            'valueData' => $this->data[$field] ?? null,
        ], ...$parameters ?? []);

        return $updatedParameters;
    }

    /**
     * Parses a validation rule and extracts its components.
     */
    private function parseRuleWithEqualSign(string $ruleItem, string $field): array
    {
        [$rule, $value] = explode(':', $ruleItem, 2);
        $parameter = explode(',', $value);

        // methods related to date rules
        $dateMethods = ['date_format', 'date_before', 'date_after', 'date_between'];

        return match (true) {
            $rule === 'same' => $this->compileSameRule($rule, $field, $parameter),
            $rule === 'unique' => $this->compileUniqueRule($rule, $parameter),
            $rule === 'between' => $this->compileBetweenRule($rule, $parameter),
            in_array($rule, $dateMethods) => $this->compileDateRule($rule, $parameter),
            $rule === 'if' => $this->compileConditionalRule($rule, $parameter),

            default => $this->compileOtherRules($rule, $parameter),
        };
    }

    /**
     * Compile a "same" validation rule.
     */
    private function compileSameRule(string $rule, string $field, array $parameter = []): array
    {
        return [
            'rule' => $rule,
            'value.' . $field => $this->data[$field] ?? null,
            'value.' . $parameter[0] => $this->data[$parameter[0]] ?? null,
            'field2' => $parameter[0]
        ];
    }

    /**
     * Compile a "unique" validation rule.
     */
    private function compileUniqueRule(string $rule, array $parameter): array
    {
        if (empty($parameter[0])) {
            throw new RuntimeException('Table name is required for the unique rule.', 1);
        }

        if (empty($parameter)) {
            throw new RuntimeException('At least one table field name is required for the unique rule.', 1);
        }

        return [
            'rule' => $rule,
            'tableName' => array_shift($parameter),  // Extract table name and remove it from the parameter array
            'tableFields' => is_array($parameter) ? $parameter : [$parameter],
        ];
    }

    /**
     * Compile the 'between' validation rule.
     */
    private function compileBetweenRule(string $rule, array $parameters): array
    {
        list($min, $max) = $parameters;

        return [
            'rule' => $rule,
            'min' => $min,
            'max' => $max,
        ];
    }

    /**
     * Compile a date validation rule.
     */
    private function compileDateRule(string $rule, array $parameters): ?array
    {
        if (empty($parameters[0])) {
            throw new RuntimeException("You must pass a parameter to the $rule rule.", 1);
        }

        $allowedRules = ['date_format', 'date_before', 'date_after'];

        if (in_array($rule, $allowedRules, true)) {
            return [
                'rule' => $rule,
                'format' => $parameters[0],
            ];
        }

        if ($rule === 'date_between') {
            return $this->compileBetweenRule('between', $parameters);
        }

        throw new \InvalidArgumentException("Invalid date rule: $rule");
    }

    /**
     * Compile a conditional rule for validation.
     */
    private function compileConditionalRule(string $rule, array $parameters): array
    {
        if (empty($parameters[0])) {
            throw new RuntimeException("You must pass a parameter to the $rule rule.", 1);
        }

        $fields = [];
        $values = [];
        foreach ($parameters as $param) {
            foreach ($this->comparisonOperators as $operator) {
                if (strpos($param, $operator) !== false) {
                    [$field, $value] = explode($operator, $param, 2);

                    $this->currentOperator = $operator;

                    $fields[] = $field;
                    $values[] = $value;
                    break; // Exit the operator loop once one is found..
                }
            }
        }

        return [
            'rule' => 'conditional',
            'fields' => $fields,
            'valueRules' => $values,
            'operator' => $this->currentOperator
        ];
    }

    /**
     * Compile other validation rules.
     */
    private function compileOtherRules(string $rule, array $parameters): array
    {
        $values = [
            'rule' => $rule,
            'valueRule' => $parameters[0],
        ];

        return $values;
    }

    /**
     * Checks if a validation rule includes a comparison operator.
     */
    private function isComparisonRule(string $rule): bool
    {
        foreach ($this->comparisonOperators as $operator) {
            if (strpos($rule, $operator) !== false) {
                $this->matchesRulesComparison = $rule;
                $this->currentOperator = $operator;

                return true;
            }
        }

        return false;
    }

    /**
     * Check if a validation rule contains a negation indicator.
     */
    private function isNegationRule(string $rule): bool
    {
        return strpos($rule, $this->not) !== false;
    }

    /**
     * Compile a comparison rule to extract the value being compared.
     */
    private function compileComparisonRule(): array
    {
        return [
            'valueRule' => str_replace($this->currentOperator, '', $this->matchesRulesComparison),
            'operator' => $this->currentOperator,
            'rule' => 'compare',
        ];
    }

    /**
     * Execute a validation rule for a field.
     */
    private function executeRule(array $parameters): void
    {
        $validator = $this->instantiateClass($parameters['rule']); // Instantiate the validator for the given rule.
        if ($validator->validate($parameters)) // Validation succeeded, no further action needed.
            return;

        // Get the validation error message.
        $errorMessage = $this->replacePlaceholders($parameters, $validator->message());
        $this->addErrorByRule($parameters['field'], $parameters['rule'], $errorMessage);
    }

    /**
     * Add an error message to the list of field errors for a specific validation rule.
     */
    protected function addErrorByRule(string $field, string $rule, string $errorMessage = null): void
    {
        $this->errors[$field][] = [
            'rule' => $rule,
            'message' => $errorMessage
        ];
    }

    /**
     * Get all errors.
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * Instantiate class rule validation
     */
    private function instantiateClass(string $classRuleName, string $nameSpace = 'Validation\\Rules\\', string $method = 'validate'): object
    {
        if (empty($classRuleName)) {
            throw new RuntimeException('The class name cannot be empty.');
        }
        // Extract the class name part before the colon, if present
        [$classNamePart] = explode(':', $classRuleName, 2);
        $class = $nameSpace . ucfirst($classNamePart);

        try {
            if (!class_exists($class)) {
                throw new RuntimeException(sprintf('Validation class [ %s ] does not exist.', $class));
            }

            $instance = $this->getOrCreateInstance($class);
            if (!method_exists($instance, $method)) {
                throw new RuntimeException(sprintf('Method [ %s ] does not exist in class [ %s ].', $method, $class));
            }

            return $instance;
        } catch (RuntimeException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Get an existing instance of the class or create a new one.
     */
    private function getOrCreateInstance(string $class): object
    {
        if (!isset($this->instanceClassValidation[$class])) {
            $callback = static::$customCallback;
            $this->instanceClassValidation[$class] = $callback
                ? new $class($callback)
                : new $class;
        }

        return $this->instanceClassValidation[$class];
    }

    /**
     * Replace placeholders in a text with corresponding values from an array.
     */
    private function replacePlaceholders(array $data, string $msg): string
    {
        // Use a regular expression to search and replace placeholders
        $msg = preg_replace_callback('/:([a-zA-Z0-9]+)/', function ($match) use ($data) {
            $key = $match[1];
            if (isset($data[$key])) {
                return $data[$key];
            }
            // If the value is not found, keep the placeholder unchanged
            return $match[0];
        }, $msg);

        return $msg;
    }
}
