Raxm allows you to lazy load components that would otherwise slow down the initial page load.

For example, imagine you have a `Revenue` component which contains a slow database query in `mount()`:

```php
<?php

namespace App\Raxm;

use App\Raxm;
use App\Models\Transaction;

class Revenue extends Component
{
    public $amount;

    public function mount()
    {
        // Slow database query...
        $this->amount = Transaction::monthToDate()->sum('amount');
    }

    public function render()
    {
        return view('raxm.revenue');
    }
}
```

```html
<div>
  Revenue this month:
  <?= $amount ?>
</div>
```

Without lazy loading, this component would delay the loading of the entire page and make your entire application feel slow.

To enable lazy loading, you can pass the `lazy` parameter into the component:

```html
<raxm:revenue lazy />
```

Now, instead of loading the component right away, Raxm will skip this component, loading the page without it. Then, when the component is visible in the viewport, Raxm will make a network request to fully load this component on the page.

## Rendering placeholder HTML

By default, Raxm will insert an empty `<div></div>` for your component before it is fully loaded. As the component will initially be invisible to users, it can be jarring when the component suddenly appears on the page.

To signal to your users that the component is being loaded, you can define a `placeholder()` method to render any kind of placeholder HTML you like, including loading spinners and skeleton placeholders:

```php
<?php

namespace App\Raxm;

use App\Raxm;
use App\Models\Transaction;

class Revenue extends Component
{
    public $amount;

    public function mount()
    {
        // Slow database query...
        $this->amount = Transaction::monthToDate()->sum('amount');
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div>
            <!-- Loading spinner... -->
            <svg>...</svg>
        </div>
        HTML;
    }

    public function render()
    {
        return view('raxm.revenue');
    }
}
```

Because the above component specifies a "placeholder" by returning HTML from a `placeholder()` method, the user will see an SVG loading spinner on the page until the component is fully loaded.

### Rendering a placeholder via a view

For more complex loaders (such as skeletons) you can return a `view` from the `placeholder()` similar to `render()`.

```php
public function placeholder(array $params = [])
{
    return view('raxm.placeholders.skeleton', $params);
}
```

Any parameters from the component being lazy loaded will be available as an `$params` arugment passed to the `placeholder()` method.

## Lazy loading outside of the viewport

By default, Lazy-loaded components aren't full loaded until they enter the browser's viewport, for example when a user scrolls to one.

If you'd rather lazy load all components on a page as soon as the page is loaded, without waiting for them to enter the viewport, you can do so by passing "on-load" into the `lazy` parameter:

```html
<raxm:revenue lazy="on-load" />
```

Now this component will load after the page is ready without waiting for it to be inside the viewport.

## Passing in props

In general, you can treat `lazy` components the same as normal components, since you can still pass data into them from outside.

For example, here's a scenario where you might pass a time interval into the `Revenue` component from a parent component:

```html
<input type="date" axm:model="start" />
<input type="date" axm:model="end" />

<raxm:revenue lazy :$start :$end />
```

You can accept this data in `mount()` just like any other component:

```php
<?php

namespace App\Raxm;

use App\Raxm;
use App\Models\Transaction;

class Revenue extends Component
{
    public $amount;

    public function mount($start, $end)
    {
        // Expensive database query...
        $this->amount = Transactions::between($start, $end)->sum('amount');
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div>
            <!-- Loading spinner... -->
            <svg>...</svg>
        </div>
        HTML;
    }

    public function render()
    {
        return view('raxm.revenue');
    }
}
```

However, unlike a normal component load, a `lazy` component has to serialize or "dehydrate" any passed-in properties and temporarily store them on the client-side until the component is fully loaded.

For example, you might want to pass in an Eloquent model to the `Revenue` component like so:

```html
<raxm:revenue lazy :$user />
```

In a normal component, the actual PHP in-memory `$user` model would be passed into the `mount()` method of `Revenue`. However, because we won't run `mount()` until the next network request, Raxm will internally serialize `$user` to JSON and then re-query it from the database before the next request is handled.

Typically, this serialization should not cause any behavioral differences in your application.

## Lazy load by default

If you want to enforce that all usages of a component will be lazy-loaded, you can add the `#[Lazy]` attribute above the component class:

```php
<?php

namespace App\Raxm;

use App\Raxm;
use Raxm\Attributes\Lazy;

#[Lazy]
class Revenue extends Component
{
    // ...
}
```

If you want to override lazy loading you can set the `lazy` parameter to `false`:

```html
<raxm:revenue :lazy="false" />
```

## Full-page lazy loading

You may want to lazy load full-page Raxm components. You can do this by calling `->lazy()` on the route like so:

```php
Route::get('/dashboard', \App\Raxm\Dashboard::class)->lazy();
```

Or alternatively, if there is a component that is lazy-loaded by default and you would like to opt-out of lazy-loading, you can use the following `enabled: false` parameter:

```php
Route::get('/dashboard', \App\Raxm\Dashboard::class)->lazy(enabled: false);
```

## Default placeholder view

If you want to set a default placeholder view for all your components you can do so by referencing the view in the `/config/raxm.php` config file:

```php
'lazy_placeholder' => 'raxm.placeholder',
```

Now, when a component is lazy-loaded and no `placeholder()` is defined, Raxm will use the configured Blade view (`raxm.placeholder` in this case.)
