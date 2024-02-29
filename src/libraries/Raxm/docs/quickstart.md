To begin your Raxm journey, we will create a simple "counter" component and render it in the browser. This example is a great way to experience Raxm for the first time as it demonstrates Raxm's _liveness_ in the simplest way possible.

## Prerequisites

Before we start, make sure you have the following installed:

- Raxm version 10 or later
- PHP version 8.1 or later

## Install Raxm

From the root directory of your Raxm app, run the following [Composer](https://getcomposer.org/) command:

```shell
composer require raxm/raxm
```

## Create a Raxm component

Raxm provides a convenient Artisan command to generate new components quickly. Run the following command to make a new `Counter` component:

```shell
php axm make:raxm counter
```

This command will generate two new files in your project:

- `app/Raxm/Counter.php`
- `resources/views/raxm/counter.blade.php`

## Writing the class

Open `app/Raxm/Counter.php` and replace its contents with the following:

```php
<?php

namespace App\Raxm;

use App\Raxm;

class Counter extends Component
{
    public $count = 1;

    public function increment()
    {
        $this->count++;
    }

    public function decrement()
    {
        $this->count--;
    }

    public function render()
    {
        return view('raxm.counter');
    }
}
```

Here's a brief explanation of the code above:

- `public $count = 1;` — Declares a public property named `$count` with an initial value of `1`.
- `public function increment()` — Declares a public method named `increment()` that increments the `$count` property each time it's called. Public methods like this can be triggered from the browser in a variety of ways, including when a user clicks a button.
- `public function render()` — Declares a `render()` method that returns a Blade view. This Blade view will contain the HTML template for our component.

## Writing the view

Open the `resources/views/raxm/counter.blade.php` file and replace its content with the following:

```html
<div>
  <h1><?= $count ?></h1>

  <button axm:click="increment">+</button>

  <button axm:click="decrement">-</button>
</div>
```

This code will display the value of the `$count` property and two buttons that increment and decrement the `$count` property, respectively.

## Register a route for the component

Open the `routes/web.php` file in your Raxm application and add the following code:

```php
use App\Raxm\Counter;

Route::get('/counter', Counter::class);
```

Now, our _counter_ component is assigned to the `/counter` route, so that when a user visits the `/counter` endpoint in your application, this component will be rendered by the browser.

## Create a template layout

Before you can visit `/counter` in the browser, we need an HTML layout for our component to render inside. By default, Raxm will automatically look for a layout file named: `resources/views/components/layouts/app.blade.php`

You may create this file if it doesn't already exist by running the following command:

```shell
php axm raxm:layout
```

This command will generate a file called `resources/views/components/layouts/app.blade.php` with the following contents:

```html
<!DOCTYPE html>
<html lang="<?= str_replace('_', '-', app()->getLocale()) ?>">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title><?= $title ?? 'Page Title' ?></title>
  </head>
  <body>
    <?= $slot ?>
  </body>
</html>
```

The _counter_ component will be rendered in place of the `$slot` variable in the template above.

You may have noticed there is no JavaScript or CSS assets provided by Raxm. That is because Raxm 3 and above automatically injects any frontend assets it needs.

## Test it out

With our component class and templates in place, our component is ready to test!

Visit `/counter` in your browser, and you should see a number displayed on the screen with two buttons to increment and decrement the number.

After clicking one of the buttons, you will notice that the count updates in real time, without the page reloading. This is the magic of Raxm: dynamic frontend applications written entirely in PHP.

We've barely scratched the surface of what Raxm is capable of. Keep reading the documentation to see everything Raxm has to offer.
