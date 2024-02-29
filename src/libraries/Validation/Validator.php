<?php

declare(strict_types=1);

namespace Validation;

use Axm;
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
    protected array $data  = [];
    protected array $errors = [];
    protected array $countErrors = [];
    protected array $occurrencesErrors = [];
    public bool $skipValidation = false;
    protected array $validationRules = [];
    public bool $skipErrorMessage = false;
    protected $errorMessage;
    private $matchesRulesComparison = [];
    private static $customCallback;

    private function __construct()
    {
    }

    /**
     * Reset the error count for a specific validation rule.
     *
     * @param string $rule The validation rule for which the error count should be reset.
     * @return void
     */
    public function resetCountError(string $rule)
    {
        // Unset the error count for the specified rule.
        unset($this->occurrencesErrors[$rule]);
    }

    /**
     * Reset the error count for specific validation rules.
     *
     * @param array $rules An array of validation rules for which the error counts should be reset.
     * @return void
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
     *
     * @param string $field   The name of the field where the error occurred.
     * @param string $rule    The name of the validation rule that failed.
     * @param string $message (Optional) Custom error message. If not provided, the default message for the rule will be used.
     *
     * @return void
     */
    public function addError(string $field, string $rule, string $message = '')
    {
        $message = $message ?: $this->errorMessage[$rule];

        $this->errors[$field][] = [
            'rule'    => $rule,
            'message' => $message
        ];

        // Track the rule in the list of rules that generated errors.
        $this->rules[] = $rule;
    }

    /**
     * Remove a validation error message for a specific field.
     *
     * @param string|null $field The name of the field for which to remove the error message.
     * @return bool Returns `true` if an error message was removed, `false` otherwise.
     */
    public function removeValidation($field = null): bool
    {
        if (isset($this->errors[$field])) {
            unset($this->errors[$field]);
            return true;
        }

        return false;
    }


    /**
     * Set the validation rules.
     *
     * @param array $rules The validation rules to be set.
     * @return bool True if the rules are set successfully, false otherwise.
     */
    public function setRules(array $rules): bool
    {
        // Check if the provided data is empty.
        if (empty($rules)) return false;

        // Reset any previous validation state.
        $this->reset();
        $this->validationRules = $rules;

        return true;
    }

    /**
     * Set the data to be validated.
     *
     * @param array $data The data to be validated.
     * @return bool True if the data is set successfully, false otherwise.
     */
    public function setData(array $data): bool
    {
        // Check if the provided data is empty.
        if (empty($data)) return false;

        $this->data = $data;
        return true;
    }

    /**
     * Add additional validation rules to the existing rules.
     *
     * @param array $rules An associative array of validation rules to add.
     * @return $this The current instance of the validator for method chaining.
     */
    public function addRules(array $rules): self
    {
        // Merges the provided rules with the existing rules.
        $this->validationRules = array_replace_recursive($this->validationRules, $rules);

        return $this;
    }

    /**
     * Get the validation rules defined for this validator.
     *
     * @return array The validation rules as an associative array.
     */
    public function getRules(): array
    {
        return $this->validationRules;
    }

    /**
     * Add an error message for a specific field and validation rule.
     *
     * @param string $field   The name of the field associated with the error.
     * @param string $rule    The validation rule that triggered the error.
     * @param string $message The error message describing the issue.
     * @param int|null $code  An optional error code for more advanced error handling.
     * @return array
     */
    public function setError(string $field, string $rule, string $message): array
    {
        $this->errors[$field] = [];
        $addError = [
            'rule'    => $rule,
            'message' => $message,
        ];

        // Add the error entry to the field's error array.
        $this->errors[$field][] = $addError;
    }

    /**
     * Check if there are errors associated with a specific field.
     *
     * @param string $field The field name to check for errors.
     * @return bool True if errors exist for the field, false otherwise.
     */
    public function hasError($field)
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get the first error message for a specific field.
     *
     * @param string $field The field name.
     * @return string The first error message for the field, or an empty string if no errors found.
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
     * @return array An array containing all error messages.
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
     * @return string The first error message found or an empty string if there are no errors.
     */
    public function getFirstError(): string
    {
        if (empty($this->errors)) return '';

        $first = $this->getErrors()[0];
        return $first;
    }

    /**
     * Resets the class to a blank slate. Should be called whenever
     * you need to process more than one array.  */
    private function reset(bool $resetValidationRules = true)
    {
        $this->rules  = [];
        $this->errors = [];

        if ($resetValidationRules) {
            $this->validationRules = [];
        }

        return $this;
    }

    /**
     * Create a new validator instance and configure it with provided rules and data.
     *
     * @param array $rules An associative array containing the validation rules.
     * @param array $data An associative array containing the data to be validated.
     * @return self The newly created validator instance configured with rules and data.
     */
    public static function make(array $rules, array $data = [], callable $customCallback = null)
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
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Validates the data.
     * @return bool True if validation passes, false otherwise.
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
     *
     * This method checks whether the validation process has failed by invoking the "startValidation" method.
     * @return bool True if the validation process has failed, false otherwise.
     */
    public function fails(): bool
    {
        return (false === $this->startValidation());
    }

    /**
     * Validate a set of data using defined validation rules.
     *
     * @param array $rulePack An associative array with the data to be validated.
     * @return bool Returns true if the data is valid according to the rules, otherwise false.
     */
    private function startValidation(): bool
    {
        // Set the data to validate and reset errors.
        $this->reset(false);

        // Iterate through validation rules for each field.
        foreach ($this->validationRules as $field => $rules) {

            // Check if the field is present in the data.
            if (!array_key_exists($field, $this->data)) continue;

            // Convert rules to an array if they are not already.
            if (!is_array($rules)) {
                $rules = $this->parseValidationRules($rules);
            }

            $count = count($rules);

            // Iterate through validation rules for the current field.
            foreach ($rules as $i => $ruleItem) {
                if (is_object($ruleItem)) {
                    $this->applyCustomValidation($ruleItem, $field);
                }

                if (is_string($ruleItem)) {
                    $this->applyValidationRules($ruleItem, $field, $i, $count);
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Parse a validation rules string into an array.
     *
     * @param string $rulesString The validation rules as a string.
     * @return array An array of validation rules.
     */
    private function parseValidationRules(string $rulesString): array
    {
        // Split the rules string by '|' and remove any empty rules.
        return array_filter(explode($this->separator, $rulesString));
    }

    /**
     * Apply a custom validation rule to a specific field.
     *
     * This function checks if the provided validation rule is an instance of the
     * `Axm\Validation\Rules\CustomRule` class, and if so, it executes it on the specified field.
     * @param mixed  $ruleItem The validation rule to apply. It should be an instance of `Axm\Validation\Rules\CustomRule`.
     * @param string $field    The name of the field to which the custom validation rule will be applied.
     * @return void
     */
    private function applyCustomValidation($ruleItem, string $field)
    {
        if ($ruleItem instanceof Axm\Validation\Rules\CustomRule) {
            // Prepare the parameters before executing the custom validation rule.
            $updatedParameters = $this->prepareParametersBeforeExecution('custom_rule', $field, []);

            // Execute the custom validation rule.
            $this->executeRule($updatedParameters);
        }
    }

    /**
     * Apply validation rules to a field based on the specified rule item.
     *
     * @param string $ruleItem The rule item to apply to the field.
     * @param string $field    The name of the field being validated.
     * @param int    $i        The current iteration index.
     * @param int    $count    The total number of validation rules for the field.
     * @throws RuntimeException If an invalid validation rule is encountered.
     */
    private function applyValidationRules(string $ruleItem, string $field, int $i, int $count)
    {
        $parameters = [];

        // Check if it's a rule with an equal sign.
        if (strpos($ruleItem, ':') !== false) {
            $parameters[] = $this->parseRuleWithEqualSign($ruleItem, $field);
        }

        // Check if it's a rule with a comparison sign.
        elseif ($this->isComparisonRule($ruleItem)) {
            $parameters[] = $this->compileComparisonRule();
        }

        // Continue only if there are more rules to apply.
        if ($i + 1 > $count) return;

        $updatedParameters = $this->prepareParametersBeforeExecution($ruleItem, $field, $parameters);

        // Execute the validation rule.
        $this->executeRule($updatedParameters);
    }

    /**
     * Prepare validation parameters before execution.
     *
     * @param string $rule       The validation rule name.
     * @param string $field      The field to validate.
     * @param array  $parameters An array of parameters associated with the rule.
     * @return array An array containing the compiled parameters.
     */
    private function prepareParametersBeforeExecution(string $rule, string $field, array $parameters)
    {
        // Determine the rule value
        $rule = $parameters[0]['rule'] ?? $parameters['rule'] ?? $rule;

        // Remove the 'rule' key from the array to avoid duplicates
        unset($parameters[0]['rule']);

        // Merge the remaining data from $parameters into the final array
        $updatedParameters = array_merge([
            'rule'  => camelCase($rule),
            'field' => $field,
            'valueData' => $this->data[$field] ?? null,
        ], ...$parameters ?? []);

        return $updatedParameters;
    }

    /**
     * Parses a validation rule and extracts its components.
     *
     * This method splits a validation rule into its rule name, field name, and values.
     * It handles special cases for 'same' and 'unique' rules.
     * @param string $ruleItem The validation rule to parse (e.g., 'ruleName:parameter').
     * @param string $field    The name of the field being validated.
     * @return array An array with the parsed rule, field name, and values.
     */
    private function parseRuleWithEqualSign($ruleItem, $field): array
    {
        [$rule, $value] = explode(':', $ruleItem, 2);
        $parameter = explode(',', $value);

        return match ($rule) {
            'same'    => $this->compileSameRule($rule, $field, $parameter),
            'unique'  => $this->compileUniqueRule($rule, $parameter),
            'between' => $this->compileBetweenRule($rule, $parameter),
            'date_format', 'date_before', 'date_after', 'date_between' => $this->compileDateRule($rule, $parameter),
            'if'      => $this->compileConditionalRule($rule, $parameter),
            default   => $this->compileOtherRules($rule, $parameter),
        };
    }

    /**
     * Compile a "same" validation rule.
     *
     * @param string $rule       The name of the rule.
     * @param string $field      The name of the field being validated.
     * @param string $parameter  The parameter used in the "same" rule.
     * @return array An array containing the compiled rule data.
     */
    private function compileSameRule($rule, $field, $parameter)
    {
        //Obtain the values of the fields involved
        $ruleValue  = $this->data[$field];
        $paramValue = $this->data[$parameter[0]];

        $values = [
            'rule'   => $rule,
            'value.' .  $field => $ruleValue,
            'value.' .  $parameter[0] => $paramValue,
            'field2' => $parameter[0]
        ];

        return $values;
    }

    /**
     * Compile a "unique" validation rule.
     *
     * @param string $rule       The name of the rule.
     * @param string $field      The name of the field being validated.
     * @param string $parameter  The parameter used in the "unique" rule.
     * @return array An array containing the compiled rule data.
     */
    private function compileUniqueRule($rule, $parameter)
    {
        $tableName = !empty($parameter[0]) ? $parameter[0] : throw new RuntimeException('You have not added the table name for the unique rule.', 1);
        unset($parameter[0]);

        $tableFields = !empty($parameter) ? $parameter : throw new RuntimeException('You have not added the table field name for the unique rule.', 1);
        $tableFields = (is_string($tableFields)) ? [$tableFields] : $tableFields;

        $values = [
            'rule' => $rule,
            'tableName' => $tableName,
            'tableFields' => $tableFields,
        ];

        return $values;
    }

    /**
     * Compile the 'between' validation rule.
     *
     * @param string $rule The name of the rule (in this case, 'between').
     * @param array $parameters An array containing the rule parameters (usually an array with 'min' and 'max' values).
     * @return array An associative array with the compiled rule and its parameters.
     */
    private function compileBetweenRule($rule, $parameters)
    {
        list($min, $max) = $parameters;

        return [
            'rule' => $rule,
            'min'  => $min,
            'max'  => $max,
        ];
    }

    /**
     * Compile a date validation rule.
     *
     * @param string $rule       The name of the validation rule.
     * @param array  $parameters An array of parameters associated with the rule.
     * @return array|null An array containing the compiled rule, or null if the rule cannot be compiled.
     * @throws RuntimeException If the 'date_format' rule is used without a valid parameter.
     */
    private function compileDateRule($rule, $parameters)
    {
        if (empty($parameters[0])) {
            throw new RuntimeException("You must pass a parameter to the $rule rule.", 1);
        }

        $allowedRules = ['date_format', 'date_before', 'date_after'];
        if (in_array($rule, $allowedRules)) {
            return [
                'rule' => $rule,
                'format' => $parameters[0],
            ];
        }

        if ($rule === 'date_between') {
            return $this->compileBetweenRule('between', $parameters);
        }
    }

    /**
     * Compile a conditional rule for validation.
     *
     * This function takes a conditional rule in the form of parameters and splits it into fields and values
     * based on a list of allowed comparison operators.
     * @param string $rule       The name of the rule.
     * @param array  $parameters The parameters of the conditional rule.
     * @throws RuntimeException If a valid parameter is not provided.
     * @return array An array with the compiled information of the conditional rule.
     */
    private function compileConditionalRule($rule, $parameters)
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
     *
     * @param string $rule The validation rule.
     * @param string $field The field being validated.
     * @param array $parameter The rule parameters.
     * @return array The compiled parameters for the validation rule.
     */
    private function compileOtherRules($rule, $parameter)
    {
        $values = [
            'rule' => $rule,
            'valueRule' => $parameter[0],
        ];

        return $values;
    }

    /**
     * Checks if a validation rule includes a comparison operator.
     *
     * @param string $rule The validation rule to inspect.
     * @return bool True if a comparison operator is found; otherwise, false.
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
     *
     * @param string $rule The validation rule to check.
     * @return bool True if the rule contains a negation indicator, false otherwise.
     */
    private function isNegationRule(string $rule): bool
    {
        return strpos($rule, $this->not) !== false;
    }

    /**
     * Compile a comparison rule to extract the value being compared.
     * @return array The extracted value from the comparison rule.
     */
    private function compileComparisonRule(): array
    {
        $match = $this->matchesRulesComparison;
        $operator = $this->currentOperator;
        $cleanValue = str_replace($operator, '', $match);
        $values = [
            'valueRule' => $cleanValue,
            'operator'  => $operator,
            'rule'      => 'compare',
        ];

        return $values;
    }

    /**
     * Execute a validation rule for a field.
     *
     * @param string $rule       The name of the validation rule.
     * @param string $field      The name of the field being validated.
     * @param mixed  $parameters The parameters for the validation rule.
     * @throws RuntimeException If too many arguments are required for the validation rule error message.
     */
    private function executeRule(array $parameters): void
    {
        $rule  = $parameters['rule'];
        $field = $parameters['field'];

        // Instantiate the validator for the given rule.
        $validator = $this->instantiateClass($rule);

        // Validation succeeded, no further action needed.
        if ($validator->validate($parameters)) return;

        // Get the validation error message.
        $msg = $validator->message();

        $errorMessage = $this->replacePlaceholders($parameters, $msg);

        $this->addErrorByRule($field, $rule, $errorMessage);
    }

    /**
     * Add an error message to the list of field errors for a specific validation rule.
     *
     * @param string      $field       The name of the field with the validation error.
     * @param string      $rule        The name of the validation rule that failed.
     * @param string|null $errorMessage The custom error message (if provided).
     * @throws RuntimeException If the validation rule is invalid or not defined.
     */
    protected function addErrorByRule(string $field, string $rule, string $errorMessage = null): void
    {
        $this->errors[$field][] = [
            'rule'    => $rule,
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
     * Instantiate and return a validation rule class instance.
     *
     * @param string $classRuleName The name of the validation rule class to instantiate.
     * @param string $method The method within the class to use for validation (default is 'validate').
     * @return object An instance of the validation rule class.
     * @throws RuntimeException If class name or method is invalid.
     */
    private function instantiateClass(string $classRuleName, string $nameSpace = 'Axm\\Validation\\Rules\\', string $method = 'validate'): object
    {
        $class = $nameSpace . ucfirst($classRuleName);

        if (empty($classRuleName)) {
            throw new RuntimeException('The class name cannot be empty.');
        }


        if (!class_exists($class)) {
            throw new RuntimeException(sprintf('Validation class [ %s ] does not exist.', $class));
        }

        $instance = $this->getOrCreateInstance($class);
        if (!method_exists($instance, $method)) {
            throw new RuntimeException(sprintf('Method [ %s ] does not exist in class [ %s ] .', $method, $class));
        }

        return $instance;
    }

    // private function instantiateClass(string $classRuleName, string $nameSpace = 'Axm\\Validation\\Rules\\', string $method = 'validate'): object
    // {
    //     $class = $nameSpace . ucfirst($classRuleName);

    //     return match (true) {
    //         empty($classRuleName) => throw new RuntimeException('The class name cannot be empty.'),
    //         !class_exists($class) => throw new RuntimeException(sprintf('Validation class [ %s ] does not exist.', $class)),
    //         !method_exists($instance = $this->getOrCreateInstance($class), $method) => throw new RuntimeException(sprintf('Method [ %s ] does not exist in class [ %s ] .', $method, $class)),

    //         default => $instance,
    //     };
    // }


    /**
     * Get an existing instance of the class or create a new one.
     *
     * @param string $class The fully qualified class name.
     * @return object An instance of the class.
     */
    private function getOrCreateInstance(string $class): object
    {
        if (!empty(static::$customCallback) && is_callable(static::$customCallback)) {

            if (!isset($this->instanceClassValidation[$class])) {
                $this->instanceClassValidation[$class] = new $class(static::$customCallback);
            }
        } elseif (!isset($this->instanceClassValidation[$class])) {
            $this->instanceClassValidation[$class] = new $class;
        }

        return $this->instanceClassValidation[$class];
    }

    /**
     * Replace placeholders in a text with corresponding values from an array.
     *
     * @param array  $data An associative array containing values for placeholders.
     * @param string $msg  The text containing placeholders to be replaced.
     * @return string The text with placeholders replaced by their values.
     */
    private function replacePlaceholders($data, $msg): string
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
