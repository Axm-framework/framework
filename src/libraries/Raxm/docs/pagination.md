Raxm's pagination feature allows you to query a subset of data and provides your users with the ability to navigate between _pages_ of those results.

Because Raxm's paginator was designed for static applications, in a non-Raxm app, each page navigation triggers a full browser visit to a new URL containing the desired page (`?page=2`).

However, when you use pagination inside a Raxm component, users can navigate between pages while remaining on the same page. Raxm will handle everything behind the scenes, including updating the URL query string with the current page.

## Basic usage

Below is the most basic example of using pagination inside a `ShowPosts` component to only show ten posts at a time:

> [!warning] You must use the `WithPagination` trait
> To take advantage of Raxm's pagination features, each component containing pagination must use the `Raxm\WithPagination` trait.

```php
<?php

namespace App\Raxm;

use Raxm\WithPagination;
use App\Raxm;
use App\Models\Post;

class ShowPosts extends Component
{
    use WithPagination;

    public function render()
    {
        return view('show-posts', [
            'posts' => Post::paginate(10),
        ]);
    }
}
```

```html
<div>
  <div>
    foreach ($posts as $post)
    <!-- ... -->
    endforeach
  </div>

  <?= $posts->links() ?>
</div>
```

As you can see, in addition to limiting the number of posts shown via the `Post::paginate()` method, we will also use `$posts->links()` to render page navigation links.

For more information on pagination using Raxm, check out [Raxm's comprehensive pagination documentation](https://laravel.com/docs/pagination).

## Resetting the page

When sorting or filtering results, it is common to want to reset the page number back to `1`.

For this reason, Raxm provides the `$this->resetPage()` method, allowing you to reset the page number from anywhere in your component.

The following component demonstrates using this method to reset the page after the search form is submitted:

```php
<?php

namespace App\Raxm;

use Raxm\WithPagination;
use App\Raxm;
use App\Models\Post;

class SearchPosts extends Component
{
    use WithPagination;

    public $query = '';

    public function search()
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('show-posts', [
            'posts' => Post::where('title', 'like', '%'.$this->query.'%')->paginate(10),
        ]);
    }
}
```

```html
<div>
  <form axm:submit="search">
    <input type="text" axm:model="query" />

    <button type="submit">Search posts</button>
  </form>

  <div>
    foreach ($posts as $post)
    <!-- ... -->
    endforeach
  </div>

  <?= $posts->links() ?>
</div>
```

Now, if a user was on page `5` of the results and then filtered the results further by pressing "Search posts", the page would be reset back to `1`.

### Available page navigation methods

In addition to `$this->resetPage()`, Raxm provides other useful methods for navigating between pages programmatically from your component:

| Method                  | Description                                 |
| ----------------------- | ------------------------------------------- |
| `$this->setPage($page)` | Set the paginator to a specific page number |
| `$this->resetPage()`    | Reset the page back to 1                    |
| `$this->nextPage()`     | Go to the next page                         |
| `$this->previousPage()` | Go to the previous page                     |

## Multiple paginators

Because both Raxm and Raxm use URL query string parameters to store and track the current page number, if a single page contains multiple paginators, it's important to assign them different names.

To demonstrate the problem more clearly, consider the following `ShowClients` component:

```php
use Raxm\WithPagination;
use App\Raxm;
use App\Models\Client;

class ShowClients extends Component
{
    use WithPagination;

    public function render()
    {
        return view('show-clients', [
            'clients' => Client::paginate(10),
        ]);
    }
}
```

As you can see, the above component contains a paginated set of _clients_. If a user were to navigate to page `2` of this result set, the URL might look like the following:

```
http://application.test/?page=2
```

Suppose the page also contains a `ShowInvoices` component that also uses pagination. To independently track each paginator's current page, you need to specify a name for the second paginator like so:

```php
use Raxm\WithPagination;
use App\Raxm;
use App\Models\Invoices;

class ShowInvoices extends Component
{
    use WithPagination;

    public function render()
    {
        return view('show-invoices', [
            'invoices' => Invoice::paginate(10, pageName: 'invoices-page'),
        ]);
    }
}
```

Now, because of the `pageName` parameter that has been added to the `paginate` method, when a user visits page `2` of the _invoices_, the URL will contain the following:

```
https://application.test/customers?page=2&invoices-page=2
```

When using Raxm's page navigation methods on a named paginator, you must provide the page name as an additional parameter:

```php
$this->setPage(2, pageName: 'invoices-page');

$this->resetPage(pageName: 'invoices-page');

$this->nextPage(pageName: 'invoices-page');

$this->previousPage(pageName: 'invoices-page');
```

## Hooking into page updates

Raxm allows you to execute code before and after a page is updated by defining either of the following methods inside your component:

```php
use Raxm\WithPagination;

class ShowPosts extends Component
{
    use WithPagination;

    public function updatingPage($page)
    {
        // Runs before the page is updated for this component...
    }

    public function updatedPage($page)
    {
        // Runs after the page is updated for this component...
    }

    public function render()
    {
        return view('show-posts', [
            'posts' => Post::paginate(10),
        ]);
    }
}
```

### Named paginator hooks

The previous hooks only apply to the default paginator. If you are using a named paginator, you must define the methods using the paginator's name.

For example, below is an example of what a hook for a paginator named `invoices-page` would look like:

```php
public function updatingInvoicesPage($page)
{
    //
}
```

### General paginator hooks

If you prefer to not reference the paginator name in the hook method name, you can use the more generic alternatives and simply receive the `$pageName` as a second argument to the hook method:

```php
public function updatingPaginators($page, $pageName)
{
    // Runs before the page is updated for this component...
}

public function updatedPaginators($page, $pageName)
{
    // Runs after the page is updated for this component...
}
```

## Using the simple theme

You can use Raxm's `simplePaginate()` method instead of `paginate()` for added speed and simplicity.

When paginating results using this method, only _next_ and _previous_ navigation links will be shown to the user instead of individual links for each page number:

```php
public function render()
{
    return view('show-posts', [
        'posts' => Post::simplePaginate(10),
    ]);
}
```

For more information on simple pagination, check out [Raxm's "simplePaginator" documentation](https://laravel.com/docs/pagination#simple-pagination).

## Using cursor pagination

Raxm also supports using Raxm's cursor pagination — a faster pagination method useful in large datasets:

```php
public function render()
{
    return view('show-posts', [
        'posts' => Post::cursorPaginate(10),
    ]);
}
```

By using `cursorPaginate()` instead of `paginate()` or `simplePaginate()`, the query string in your application's URL will store an encoded _cursor_ instead of a standard page number. For example:

```
https://example.com/posts?cursor=eyJpZCI6MTUsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0
```

For more information on cursor pagination, check out [Raxm's cursor pagination documentation](https://laravel.com/docs/pagination#cursor-pagination).

## Using Bootstrap instead of Tailwind

If you are using [Bootstrap](https://getbootstrap.com/) instead of [Tailwind](https://tailwindcss.com/) as your application's CSS framework, you can configure Raxm to use Bootstrap styled pagination views instead of the default Tailwind views.

To accomplish this, set the `pagination_theme` configuration value in your application's `config/raxm.php` file:

```php
'pagination_theme' => 'bootstrap',
```

> [!info] Publishing Raxm's configuration file
> Before customizing the pagination theme, you must first publish Raxm's configuration file to your application's `/config` directory by running the following command:
>
> ```shell
> php axm raxm:publish --config
> ```

## Modifying the default pagination views

If you want to modify Raxm's pagination views to fit your application's style, you can do so by _publishing_ them using the following command:

```shell
php axm raxm:publish --pagination
```

After running this command, the following four files will be inserted into the `resources/views/vendor/raxm` directory:

| View file name               | Description                             |
| ---------------------------- | --------------------------------------- |
| `tailwind.blade.php`         | The standard Tailwind pagination theme  |
| `tailwind-simple.blade.php`  | The _simple_ Tailwind pagination theme  |
| `bootstrap.blade.php`        | The standard Bootstrap pagination theme |
| `bootstrap-simple.blade.php` | The _simple_ Bootstrap pagination theme |

Once the files have been published, you have complete control over them. When rendering pagination links using the paginated result's `->links()` method inside your template, Raxm will use these files instead of its own.

## Using custom pagination views

If you wish to bypass Raxm's pagination views entirely, you can render your own in one of two ways:

1. The `->links()` method in your Blade view
2. The `paginationView()` method in your component

### Via `->links()`

The first approach is to simply pass your custom pagination Blade view name to the `->links()` method directly:

```html
<?= $posts->links('custom-pagination-links') ?>
```

When rendering the pagination links, Raxm will now look for a view at `resources/views/custom-pagination-links.blade.php`.

### Via `paginationView()`

The second approach is to declare a `paginationView` method inside your component which returns the name of the view you would like to use:

```php
public function paginationView()
{
    return 'custom-pagination-links-view';
}
```

### Sample pagination view

Below is an unstyled sample of a simple Raxm pagination view for your reference.

As you can see, you can use Raxm's page navigation helpers like `$this->nextPage()` directly inside your template by adding `axm:click="nextPage"` to buttons:

```html
<div>
  if ($paginator->hasPages())
  <nav role="navigation" aria-label="Pagination Navigation">
    <span>
      if ($paginator->onFirstPage())
      <span>Previous</span>
      @else
      <button axm:click="previousPage" axm:loading.attr="disabled" rel="prev">
        Previous
      </button>
      endif
    </span>

    <span>
      if ($paginator->onLastPage())
      <span>Next</span>
      @else
      <button axm:click="nextPage" axm:loading.attr="disabled" rel="next">
        Next
      </button>
      endif
    </span>
  </nav>
  endif
</div>
```
