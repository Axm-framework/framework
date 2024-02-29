<p align="center">
    <a href="https://packagist.org/packages/axm/validation">
        <img src="https://poser.pugx.org/axm/validation/d/total.svg" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/axm/validation">
        <img src="https://poser.pugx.org/axm/validation/v/stable.svg" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/axm/validation">
        <img src="https://poser.pugx.org/axm/validation/license.svg" alt="License">
    </a>
</p>

Certainly! Here's comprehensive documentation for the `Validator` class along with examples of how to use it. This documentation is formatted for GitHub:

### Validator Class

The `Validator` class is a versatile tool for data validation in PHP applications. It allows you to define validation rules and check if data meets those rules. This documentation provides an in-depth guide to using the `Validator` class.

#### Table of Contents
1. [Class Overview](#class-overview)
2. [Installation](#installation)
3. [Basic Usage](#basic-usage)
4. [Validation Rules](#validation-rules)
5. [Advanced Usage](#advanced-usage)
6. [Examples](#examples)
7. [Contributing](#contributing)
8. [License](#license)

---

#### 1. Class Overview

The `Validator` class provides the following features:
- Validation of data against user-defined rules.
- Support for custom validation rules.
- Rule chaining for complex validation scenarios.
- Detailed error reporting with customizable error messages.

#### 2. Installation

To use the `Validator` class, follow these steps:

```bash
composer require axm/validation
```

#### 3. Basic Usage

Here's a simple example of how to use the `Validator` class:

```php
use Validation\Validator;

$rules = [
    'username' => 'required|string|min:5|max:20',
    'email' => 'required|email',
];

$data = [
    'username' => 'john_doe',
    'email' => 'johndoe@example.com',
];

$validator = Validator::make($rules, $data);

if ($validator->validate()) {
    echo "Data is valid!";
} else {
    $errors = $validator->getErrors();
    print_r($errors);
}
```

#### 4. Validation Rules

The `Validator` class supports a variety of validation rules, including `required`, `string`, `email`, `min`, `max`, and custom rules. You can chain rules together using the `|` separator.

##### Available Rules

- `required`: Ensures the field is present and not empty.
- `string`: Checks if the field is a string.
- `email`: Validates the field as an email address.
- `min:value`: Checks if the field's length or value is greater than or equal to `value`.
- `max:value`: Checks if the field's length or value is less than or equal to `value`.
- Custom Rules: You can define your own custom validation rules.

#### 5. Advanced Usage

##### Custom Validation Rules

You can create custom validation rules by defining a class that implements the `Axm\Validation\Rules\RuleInterface`. Here's an example:

```php
use Validation\Rules\RuleInterface;

class CustomRule implements RuleInterface
{
    public function validate($value, array $parameters = []): bool
    {
        // Implement your custom validation logic here.
        return /* validation result */;
    }

    public function message(): string
    {
        // Define a custom error message for this rule.
        return 'Custom validation failed.';
    }
}
```

##### Adding Custom Rules

To add custom rules to the `Validator`, use the `addRules` method:

```php
$customRules = [
    'custom_rule' => CustomRule::class,
];

$validator->addRules($customRules);
```

#### 6. Examples

Here are more examples of how to use the `Validator` class:

##### Example 1: Conditional Validation

```php
$rules = [
    'password' => 'required|string|min:8',
    'confirm_password' => 'required|string|same:password',
];

$data = [
    'password' => 'mysecurepassword',
    'confirm_password' => 'mysecurepassword',
];

$validator = Validator::make($rules, $data);
```

##### Example 2: Unique Rule

```php
$rules = [
    'email' => 'required|string|unique:users,email',
];

$data = [
    'email' => 'johndoe@example.com',
];

$validator = Validator::make($rules, $data);
```

#### 7. Contributing

If you want to contribute to the `Validator` class, please follow the [contributing guidelines](https://github.com/axm/validation/blob/main/CONTRIBUTING.md).

#### 8. License

The `Validator` class is open-source software licensed under the [MIT License](https://github.com/axm/validation/blob/main/LICENSE).

---

This documentation should help you get started with the `Validator` class and demonstrate various use cases. For more details and advanced usage, refer to the class source code and examples provided in the [GitHub repository](https://github.com/axm/validation).

Feel free to adapt and expand upon this documentation as needed for your project.
