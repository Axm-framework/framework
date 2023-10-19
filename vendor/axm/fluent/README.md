<!-- markdownlint-disable no-inline-html -->
<p align="center">
  <br><br>
  <img src="art/readme_logo.png/" height="120"/>
  <br>
</p>

<p align="center">
	<a href="https://packagist.org/packages/axm/fluent"
		><img
			src="https://poser.pugx.org/axm/fluent/v/stable"
			alt="Latest Stable Version"
	/></a>
	<a href="https://packagist.org/packages/axm/fluent"
		><img
			src="https://poser.pugx.org/axm/fluent/downloads"
			alt="Total Downloads"
	/></a>
	<a href="https://packagist.org/packages/axm/fluent"
		><img
			src="https://poser.pugx.org/axm/fluent/license"
			alt="License"
	/></a>
</p>
<br />
<br />

# Fluent Interface

The `FluentInterface` class is a PHP tool that provides a fluent interface for chaining methods and controlling the flow of operations concisely in PHP fluentlications. Its main purpose is to improve code readability, allowing you to chain a series of methods coherently and easy to follow. This is the greatest potential of this interface, it is no longer necessary to repeatedly return the $this object, now `FluentInterface` handles it automatically for you.

## 📦 Installation

You can also use [Composer](https://getcomposer.org/) to install Axm in your project quickly.

```bash
composer require axm/fluent
```

## Method Chaining

You can chain multiple methods, which facilitates executing multiple operations sequentially. For example:

```php
$result = __(new MyClass)
    ->method1()
    ->method2()
    ->method3()
    ->get();
```

## Flow Control

The class allows precise control of the method execution flow using if, elseif and else conditions. This is useful to run specific operations based on certain conditions. For example:

```php
$result = __(User::class)
    ->if(fn ($user) => $user->isAdmin())
    ->setProperty('type', 'admin')
    ->return('You are an admin.')

    ->elseif(fn ($user) => $user->isUser())
    ->setProperty('type', 'user')
    ->return('You are a user.')

    ->else()
    ->setProperty('type', 'unknown')
    ->return('You are not logged in.')
    ->get();
```

## Dynamic Instance Creation

You can dynamically create class instances and set them as the current object. This is useful when you need to work with different objects flexibly. For example:

```php
$result = __(new MyClass)  //new instance
    ->method1()
    ->method2()
    ->new('SomeClass')    //resets the previous intent with a new `FluentInterface` class to continue chaining.
    ->method1()
    ->method2()
    ->method3()
    ->new('OtherClass')    //resets the previous intent with a new `FluentInterface` class to continue chaining.
    ->method1()
    ->method2()
    ->method3()

    ->get('method1');   //gets the value in this case of the return of method 1 of the last instance
```

## Exception Handling

The FluentInterface class handles exceptions and allows you to throw exceptions during execution, making condition-based decisions and thrown exceptions easier.

```php
$result = __(new MyClass)
    ->boot()
    ->getErrors()
    ->throwIf(fn ($user) => $fi->get('getError') > 0, , 'An error has occurred while initializing.')
```

## Debugging Functions

It offers methods like dd(), dump(), and echo() to aid in debugging and analyzing results.

##### dd() method

The dd() method (dump and die) is used to debug and analyze the results of the FluentInterface class. You can use it to show the contents of the current variable in detail. If you provide a key ($key), only that specific entry in the results will be shown.
Usage example:

```php
$result = __(new MyClass)
    ->increment(10)
    ->duplicate()
    ->dd();
```

##### dump() method

The dump() method is used to debug and analyze the results of the FluentInterface class. As with dd(), you can provide a key ($key) to show only a specific entry in the results.
Usage example:

```php
$result = __(new MyClass)
    ->increment(10)
    ->duplicate()
    ->dump('duplicate');       //will return the value of the duplicate method call.
```

##### echo() method

The echo() method is used to print the results of the FluentInterface class. You can optionally provide a value ($value) to print something specific. If no value is provided, it will print all current results.
Usage example:

```php
$result = __(new MyClass)
    ->increment(10)
    ->duplicate()
    ->echo();
```

## Using Custom Methods

In addition to the built-in methods, you can add your own custom methods using addCustomMethod(). This extends the functionality of the class according to your specific needs.

```php
$result = __(new MyClass)
    ->addCustomMethod('call', function ($obj) {
    // Define your own logic here
    })
    ->addCustomMethod('getName', function ($obj) {
    // Define your own logic here
    })
    ->call()
    ->getName()
    ->all();
```

## Interface with Laravel Collections

The class can work with Laravel collections and run collection methods on them. You just need to pass an array as an argument to the FluentInterface class.
For example:

```php
$collection = [
    ['name' => 'John Doe',  'age' => 30],
    ['name' => 'Jane Doe',  'age' => 25],
    ['name' => 'John Smith','age' => 40],
];

$result = __($collection)
    ->filter(function ($item) {
        return $item['age'] > 25;
    })
    ->sort(function ($a, $b) {
        return $a['name'] <=> $b['name'];
    });

$result->all();
```

##### Usage Examples of Fluent Interface with an object.

Example 1: Operations on a Numeric Value
This example illustrates the use of the MyClass class, which provides a fluent interface to perform operations on a numeric value. The MyClass class has three main methods: `increment()`, `duplicate()`, and `getValue()`.

```php
class MiClase
{
    public $value = 0;

    public function increment($cantidad)
    {
        return $this->value += $cantidad;
    }

    public function duplicate()
    {
        return $this->value *= 2;
    }

    public function getValue()
    {
        return $this->value;
    }
}
```

Fluent Interface implementation:

```php
$res = __(MiClase::class)
    ->increment(5)
    ->duplicate()
    ->increment(5)

    ->if(fn ($fi) => $fi->value > 20)
    ->increment(5)

    ->elseif(fn ($fi) => $fi->value < 15)
    ->increment(10)

    ->else()
    ->increment(10)

    ->getValue()->dd('getValue');
```

Example 2: User Input Validation
This example shows how to validate user input and make decisions based on specific conditions. The FluentInterface class provides a fluent interface to chain methods and control the execution flow effectively.

```php
__($request->input())   //input array

    ->if(fn ($item) => $item['name'] === '')
    ->throwIf(true, 'The name field is required.')

    ->if(fn ($item) => $item['email'] === '')
    ->throwIf(true, 'The email field is required.');

    ->new(Auth::class)     // Create a new instance of the class 'Auth'.
    ->if(fn ($user) => $user->hasPermission('admin'))
    ->return(['Admin Dashboard', 'User Management','Role Management',])

    ->elseif(fn ($user) => $user->hasPermission('user'))
    ->return(['My Profile','My Orders','My Account',])

    ->else()
    ->return(['Login','Register',])
    ->get('return');
```

Example 3: Dynamic Report Generation
Imagine you are developing a report generation fluentlication that allows users to configure and customize reports according to their needs. In this situation, FluentInterface can simplify the building of dynamic reports.

Suppose you have a ReportBuilder class that is used to build reports. You can use FluentInterface to chain methods and dynamically configure report components like headers, charts, data, and output formats.

```php
// Create a custom report using FluentInterface.
__(ReportBuilder::class)
    ->setHeader('Informe de Ventas')
    ->setSubtitle('Resultados mensuales')
    ->setChart('Ventas por mes', 'bar')
    ->addData('Enero', 1000)
    ->addData('Febrero', 1200)
    ->addData('Marzo', 800)
    ->setFooter('© 2023 Mi Empresa')
    ->setFormat('PDF')
    ->generateReport();
```

Example 4: Building Configurable Forms
Imagine you are developing a form builder platform where users can design their own forms with custom fields. FluentInterface can simplify the creation and manipulation of dynamic forms.

```php
 __(FormBuilder::class)
    ->setTitle('Contact Form')
    ->addField('Name', 'text')
    ->addField('Email', 'email')
    ->addField('Message', 'textarea')
    ->addButton('Submit', 'submit')
    ->setAction('/submit-form')
    ->setMethod('POST')
    ->generateForm();
```

Example 5: Sending Custom Emails
Suppose you are developing an fluentlication that sends custom emails to users. FluentInterface can simplify the building of these emails.

```php
__(EmailBuilder::class)
    ->setRecipient('user@example.com')
    ->setSubject('Welcome!')
    ->setBody('Hi, [name]. Thank you for joining our website.')
    ->addAttachment('invoice.pdf')
    ->setSender('info@mycompany.com')
    ->send();
```

Example 6: Generating Dynamic SQL Queries
Suppose you are developing a web fluentlication that needs to generate dynamic SQL queries to interact with a database. You can use FluentInterface to build these queries programmatically and readably:

```php
$query = __(QueryBuilder::class)
    ->select('name', 'email')
    ->from('users')
    ->where('age', '>', 25)
    ->andWhere('city', '=', 'New York')
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->execute();
```

Example 7: Creating Interactive Charts
Suppose you are developing a web fluentlication that displays interactive charts to users. FluentInterface can help you construct and configure these charts flexibly:

```php
$chart = __(ChartBuilder::class)
    ->setType('line')
    ->setTitle('Monthly Sales')
    ->addData('January', [100, 150, 200, 120])
    ->addData('February', [120, 160, 180, 140])
    ->setXAxisLabels(['Week 1', 'Week 2', 'Week 3', 'Week 4'])
    ->setYAxisLabel('Sales (in thousands)')
    ->render();
```
