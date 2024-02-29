Like many other component-based frameworks, Raxm components are nestable — meaning one component can render multiple components within itself.

However, because Raxm's nesting system is built differently than other frameworks, there are certain implications and constraints that are important to be aware of.

> [!tip] Make sure you understand hydration first
> Before learning more about Raxm's nesting system, it's helpful to fully understand how Raxm hydrates components. You can learn more by reading the [hydration documentation](/docs/hydration).

## Every component is an island

In Raxm, every component on a page tracks its state and makes updates independently of other components.

For example, consider the following `Posts` and nested `ShowPost` components:

```php
<?php

namespace App\Raxm;

use Illuminate\Support\Facades\Auth;
use App\Raxm;

class Posts extends Component
{
    public $postLimit = 2;

    public function render()
    {
        return view('raxm.posts', [
            'posts' => Auth::user()->posts()
                ->limit($this->postLimit)->get(),
        ]);
    }
}
```

```html
<div>
    Post Limit: <input type="number" axm:model.live="postLimit">

    foreach ($posts as $post)
        <raxm:show-post :$post :key="$post->id">
    endforeach
</div>
```

```php
<?php

namespace App\Raxm;

use Illuminate\Support\Facades\Auth;
use App\Raxm;
use App\Models\Post;

class ShowPost extends Component
{
    public Post $post;

    public function render()
    {
        return view('raxm.show-post');
    }
}
```

```html
<div>
  <h1><?= $post->title ?></h1>

  <p><?= $post->content ?></p>

  <button axm:click="$refresh">Refresh post</button>
</div>
```

Here's what the HTML for the entire component tree might look like on initial page load:

```html
<div axm:id="123" axm:snapshot="...">
  Post Limit: <input type="number" axm:model.live="postLimit" />

  <div axm:id="456" axm:snapshot="...">
    <h1>The first post</h1>

    <p>Post content</p>

    <button axm:click="$refresh">Refresh post</button>
  </div>

  <div axm:id="789" axm:snapshot="...">
    <h1>The second post</h1>

    <p>Post content</p>

    <button axm:click="$refresh">Refresh post</button>
  </div>
</div>
```

Notice that the parent component contains both its rendered template and the rendered templates of all the components nested within it.

Because each component is independent, they each have their own IDs and snapshots (`axm:id` and `axm:snapshot`) embedded in their HTML for Raxm's JavaScript core to extract and track.

Let's consider a few different update scenarios to see the differences in how Raxm handles different levels of nesting.

### Updating a child

If you were to click the "Refresh post" button in one of the child `show-post` components, here is what would be sent to the server:

```js
{
    memo: { name: 'show-post', id: '456' },

    state: { ... },
}
```

Here's the HTML that would get sent back:

```html
<div axm:id="456">
  <h1>The first post</h1>

  <p>Post content</p>

  <button axm:click="$refresh">Refresh post</button>
</div>
```

The important thing to note here is that when an update is triggered on a child component, only that component's data is sent to the server, and only that component is re-rendered.

Now let's look at the less intuitive scenario: updating a parent component.

### Updating the parent

As a reminder, here's the Blade template of the parent `Posts` component:

```html
<div>
    Post Limit: <input type="number" axm:model.live="postLimit">

    foreach ($posts as $post)
        <raxm:show-post :$post :key="$post->id">
    endforeach
</div>
```

If a user changes the "Post Limit" value from `2` to `1`, an update will be solely triggered on the parent.

Here's an example of what the request payload might look like:

```js
{
    updates: { postLimit: 1 },

    snapshot: {
        memo: { name: 'posts', id: '123' },

        state: { postLimit: 2, ... },
    },
}
```

As you can see, only the snapshot for the parent `Posts` component is sent along to the server.

An important question you might be asking yourself is: what happens when the parent component re-renders and encounters the child `show-post` components? How will it re-render the children if their snapshots haven't been included in the request payload?

The answer is: they won't be re-rendered.

When Raxm renders the `Posts` component, it will render placeholders for any child components it encounters.

Here is an example of what the rendered HTML for the `Posts` component might be after the above update:

```html
<div axm:id="123">
  Post Limit: <input type="number" axm:model.live="postLimit" />

  <div axm:id="456"></div>
</div>
```

As you can see, only one child has been rendered because `postLimit` was updated to `1`. However, you will also notice that instead of the full child component, there is only an empty `<div></div>` with the matching `axm:id` attribute.

When this HTML is received on the frontend, Raxm will _morph_ the old HTML for this component into this new HTML, but intelligently skip any child component placeholders.

The effect is that, after _morphing_, the final DOM content of the parent `Posts` component will be the following:

```html
<div axm:id="123">
  Post Limit: <input type="number" axm:model.live="postLimit" />

  <div axm:id="456">
    <h1>The first post</h1>

    <p>Post content</p>

    <button axm:click="$refresh">Refresh post</button>
  </div>
</div>
```

## Performance implications

Raxm's "island" architecture can have both positive and negative implications for your application.

An advantage of this architecture is it allows you to isolate expensive portions of your application. For example, you can quarantine a slow database query to its own independent component, and its performance overhead won't impact the rest of the page.

However, the biggest drawback of this approach is that because components are entirely separate, inter-component communication/dependencies becomes more difficult.

For example, if you had a property passed down from the above parent `Posts` component to the nested `ShowPost` component, it wouldn't be "reactive". Because each component is an island, if a request to the parent component changed the value of the property being passed into `ShowPost`, it wouldn't update inside `ShowPost`.

Raxm has overcome a number of these hurdles and provides dedicated APIs for these scenarios like: [Reactive properties](/docs/nesting#reactive-props), [Modelable components](/docs/nesting#binding-to-child-data-using-wiremodel), and [the `$parent` object](/docs/nesting#directly-accessing-the-parent-from-the-child).

Armed with this knowledge of how nested Raxm components operate, you will be able to make more informed decisions about when and how to nest components within your application.
