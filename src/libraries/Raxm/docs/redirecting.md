After a user performs some action — like submitting a form — you may want to redirect them to another page in your application.

Because Raxm requests aren't standard full-page browser requests, standard HTTP redirects won't work. Instead, you need to trigger redirects via JavaScript. Fortunately, Raxm exposes a simple `$this->redirect()` helper method to use within your components. Internally, Raxm will handle the process of redirecting on the frontend.

If you prefer, you can use [Raxm's built-in redirect utilities](https://laravel.com/docs/responses#redirects) within your components as well.

## Basic usage

Below is an example of a `CreatePost` Raxm component that redirects the user to another page after they submit the form to create a post:

```php
<?php

namespace App\Raxm;

use App\Raxm;
use App\Models\Post;

class CreatePost extends Component
{
	public $title = '';

    public $content = '';

    public function save()
    {
		Post::create([
			'title' => $this->title,
			'content' => $this->content,
		]);

		$this->redirect('/posts'); // [tl! highlight]
    }

    public function render()
    {
        return view('raxm.create-post');
    }
}
```

As you can see, when the `save` action is triggered, a redirect will also be triggered to `/posts`. When Raxm receives this response, it will redirect the user to the new URL on the frontend.

## Redirecting to full-page components

Because Raxm uses Raxm's built-in redirection feature, you can use all of the redirection methods available to you in a typical Raxm application.

For example, if you are using a Raxm component as a full-page component for a route like so:

```php
use App\Raxm\ShowPosts;

Route::get('/posts', ShowPosts::class);
```

You can redirect to the component by providing the component name to the `redirect()` method:

```php
public function save()
{
    // ...

    $this->redirect(ShowPage::class);
}
```

## Flash messages

In addition to allowing you to use Raxm's built-in redirection methods, Raxm also supports Raxm's [session flash data utilities](https://laravel.com/docs/session#flash-data).

To pass flash data along with a redirect, you can use Raxm's `session()->flash()` method like so:

```php
use App\Raxm;

class UpdatePost extends Component
{
    // ...

    public function update()
    {
        // ...

        session()->flash('status', 'Post successfully updated.');

        $this->redirect('/posts');
    }
}
```

Assuming the page being redirected to contains the following Blade snippet, the user will see a "Post successfully updated." message after updating the post:

```html
if (session('status'))
<div class="alert alert-success">
  <?= session('status') ?>
</div>
endif
```
