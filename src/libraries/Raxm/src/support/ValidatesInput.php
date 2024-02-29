<?php

declare(strict_types=1);

namespace Raxm\Support;

use Validation\Validator;


/**
 * A trait for handling input validation and error tracking.
 */
trait ValidatesInput
{
    /**
     * @var array The error bag to store validation errors.
     */
    protected $errorBag;

    /**
     * @var callable|null Callback to execute with the validator.
     */
    protected $withValidatorCallback;

    /**
     * Get the error bag containing validation errors.
     * @return array The error bag.
     */
    public function getErrorBag()
    {
        return $this->errorBag ?? [];
    }

    /**
     * Check if there are errors in the error bag.
     * @return bool Whether the error bag is not empty.
     */
    public function hasErrorBag()
    {
        return !empty($this->errorBag);
    }

    /**
     * Add an error to the error bag.
     *
     * @param string $name    The name of the error.
     * @param string $message The error message.
     * @return string The added error message.
     */
    public function addError($name, $message)
    {
        return $this->errorBag[$name] = $message;
    }

    /**
     * Set the error bag to a specific value.
     *
     * @param array $bag The error bag to set.
     * @return array The set error bag.
     */
    public function setErrorBag($bag)
    {
        return $this->errorBag = !empty($bag)
            ? $bag
            : [];
    }

    /**
     * Reset the error bag for a given field or all fields if no field is provided.
     * If a field is provided, only the errors for that field will be removed.
     *
     * @param string|array $field The name of the field to reset the errors for. If null or an empty array, the entire error bag will be reset.
     */
    public function resetErrorBag($field = null)
    {
        $fields = (array) $field;

        if (empty($fields)) {
            return $this->errorBag = [];
        }

        $this->setErrorBag(
            $this->errorBagExcept($fields)
        );
    }

    /**
     * Remove validation rules for a given field or all fields if no field is provided
     *
     * @param  mixed $field
     * @return void
     */
    public function removeValidation($field = null)
    {
        return Validator::getInstance()
            ->removeValidation($field);
    }

    /**
     * Reset validation errors for a specific field or all fields if no field is provided
     *
     * @param  mixed $field
     * @return void
     */
    public function resetValidation($field = null)
    {
        $this->resetErrorBag($field);
    }

    /**
     * Filter validation errors and exclude the specified fields
     *
     * @param array $fields The fields to exclude from the error bag
     * @return array The filtered error bag
     */
    public function errorBagExcept($fields)
    {
        $filteredErrors = [];
        foreach ($this->errorBag as $key => $messages) {
            if (!in_array($key, $fields)) {
                $filteredErrors[$key] = $messages;
            }
        }

        return $filteredErrors;
    }

    /**
     * Get the validation rules defined by the subclass.
     * @return array The validation rules.
     */
    protected function getRules()
    {
        if (method_exists($this, 'rules')) return $this->rules();
        if (property_exists($this, 'rules')) return $this->rules;

        return [];
    }

    /**
     * Get the validation messages defined by the subclass.
     * @return array The validation messages.
     */
    protected function getMessages()
    {
        if (method_exists($this, 'messages')) return $this->messages();
        if (property_exists($this, 'messages')) return $this->messages;

        return [];
    }

    /**
     * Get the validation attributes defined by the subclass.
     * @return array The validation attributes.
     */
    protected function getValidationAttributes()
    {
        if (method_exists($this, 'validationAttributes')) return $this->validationAttributes();
        if (property_exists($this, 'validationAttributes')) return $this->validationAttributes;

        return [];
    }

    /**
     * Get the validation custom values defined by the subclass.
     * @return array The validation custom values.
     */
    protected function getValidationCustomValues()
    {
        if (method_exists($this, 'validationCustomValues')) return $this->validationCustomValues();
        if (property_exists($this, 'validationCustomValues')) return $this->validationCustomValues;

        return [];
    }

    /**
     * Perform validation on the provided input data using defined rules.
     *
     * @param  mixed $rules
     * @param  mixed $name
     * @return array
     */
    function rulesForModel($rules, $name): array
    {
        $filteredRules = [];

        if (empty($rules)) {
            return $filteredRules;
        }

        foreach ($rules as $key => $value) {
            if ($this->beforeFirstDot($key) === $name) {
                $filteredRules[$key] = $value;
            }
        }

        return $filteredRules;
    }

    /**
     * Check if a rule exists for the given dot-notated property.
     *
     * @param  mixed $dotNotatedProperty
     * @return bool
     */
    public function hasRuleFor($dotNotatedProperty): bool
    {
        $rules = $this->getRules();
        $propertyWithStarsInsteadOfNumbers = $this->ruleWithNumbersReplacedByStars($dotNotatedProperty);

        // If the property has numeric indexes on it,
        if ($dotNotatedProperty !== $propertyWithStarsInsteadOfNumbers) {
            $ruleKeys = array_keys($rules);
            return in_array($propertyWithStarsInsteadOfNumbers, $ruleKeys);
        }

        $ruleKeys = array_keys($rules);
        $filteredKeys = array_map(function ($key) {
            return explode('.*', $key)[0];
        }, $ruleKeys);

        return in_array($dotNotatedProperty, $filteredKeys);
    }

    /**
     * Replaces numbers in a dot-notated property string with asterisks.
     *
     * @param  string $dotNotatedProperty The dot-notated property string.
     * @return string The modified dot-notated property string with numbers replaced by asterisks.
     */
    public function ruleWithNumbersReplacedByStars($dotNotatedProperty)
    {
        return preg_replace('/\d+/', '*', $dotNotatedProperty);
    }

    /**
     * Checks if a rule exists for a given dot-notated property.
     *
     * @param  mixed $dotNotatedProperty The dot-notated property string.
     * @return bool True if a rule exists, false otherwise.
     */
    public function missingRuleFor($dotNotatedProperty): bool
    {
        return !$this->hasRuleFor($dotNotatedProperty);
    }

    /**
     * Iterates through the rules and checks if any rule matches the given data.
     *
     * @param  array $rules The array of rules.
     * @param  array $data The array of data to check against the rules.
     */
    protected function checkRuleMatchesProperty($rules, $data)
    {
        foreach (array_keys($rules) as $ruleKey) {
            if (!array_key_exists($this->beforeFirstDot($ruleKey), $data)) {
                throw new \Exception('No property found for validation: [' . $ruleKey . ']');
            }
        }
    }

    /**
     * Perform validation on the provided input data using defined rules.
     * @return array
     */
    public function validate()
    {
        $packRules = $this->getRules();

        if (empty($packRules)) return [];

        $inputData  = $this->serverMemo['data'] ?? [];
        $this->validateCompile($inputData, $packRules);
    }

    /**
     * Perform validation on a specific field using defined rules.
     * @param string $field The field to validate.
     */
    public function validateOnly($field)
    {
        $packRules = $this->getRules();
        if (empty($packRules)) return [];

        $data  = data_get($this->updates, '0.payload');

        if (!isset($data['name'])) return;

        $name  = $data['name'];
        $value = $data['value'];

        $inputData  = [$name => $value];
        $this->validateCompile($inputData, $packRules);
    }

    /**
     * Compile and execute validation based on provided input data and rules.
     *
     * @param array $inputData The input data to validate.
     * @param array $packRules The validation rules to apply.
     */
    public function validateCompile($inputData, $packRules)
    {
        $matchRules = array_intersect_key($inputData, $packRules);
        $validator  = Validator::make($packRules, $matchRules);

        if ($validator->fails()) {
            foreach ($matchRules as $field => $rule) {
                $this->addError($field, $validator->getFirstErrorByField($field));
            }
        }

        $this->messages = $this->getErrorBag();
    }

    /**
     * Get the data to be used for validation.
     * @return array The data for validation.
     */
    protected function getDataForValidation()
    {
        return $this->getPublicPropertiesDefinedBySubClass();
    }
}
