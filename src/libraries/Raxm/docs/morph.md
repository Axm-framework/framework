When a Raxm component updates the browser's DOM, it does so in an intelligent way we call "morphing". The term _morph_ is in contrast with a word like _replace_.

Instead of _replacing_ a component's HTML with newly rendered HTML every time a component is updated, Raxm dynamically compares the current HTML with the new HTML, identifies differences, and makes surgical changes to the HTML only in the places where changes are needed.

This has the benefit of preserving existing, un-changed elements on a component. For example, event listeners, focus state, and form input values are all preserved between Raxm updates. Of course, morphing also offers increased performance compared to wiping and re-rending new DOM on every update.

## How morphing works

To understand how Raxm determines which elements to update between Raxm requests, consider this simple `Todos` component:

```php
class Todos extends Component
{
    public $todo = '';

    public $todos = [
        'first',
        'second',
    ];

    public function add()
    {
        $this->todos[] = $this->todo;
    }
}
```

```html
<div axm:submit="add">
  <ul>
    foreach ($todos as $todo)
    <li><?= $todo ?></li>
    endforeach
  </ul>

  <input axm:model="todo" />
</div>
```

The initial render of this component will output the following HTML:

```html
<form axm:submit="add">
  <ul>
    <li>first</li>

    <li>second</li>
  </ul>

  <input axm:model="todo" />
</form>
```

Now, imagine you typed "third" into the input field and pressed the `[Enter]` key. The newly rendered HTML would be:

```html
<form axm:submit="add">
  <ul>
    <li>first</li>

    <li>second</li>

    <li>third</li>
    <!-- [tl! add] -->
  </ul>

  <input axm:model="todo" />
</form>
```

When Raxm processes the component update, it _morphs_ the original DOM into the newly rendered HTML. The following visualization should intuitively give you an understanding of how it works:

<div style="padding:56.25% 0 0 0;position:relative;"><iframe src="https://player.vimeo.com/video/844600772?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;" title="morph_basic"></iframe></div><script src="https://player.vimeo.com/api/player.js"></script>

As you can see, Raxm walks both HTML trees simultaneously. As it encounters each element in both trees, it compares them for changes, additions, and removals. If it detects one, it surgically makes the appropriate change.

## Morphing shortcomings

The following are scenarios where morphing algorithms fail to correctly identify the change in HTML trees and therefore cause problems in your application.

### Inserting intermediate elements

Consider the following Raxm Blade template for a fictitious `CreatePost` component:

```html
<form axm:submit="save">
  <div>
    <input axm:model="title" />
  </div>

  if ($errors->has('title'))
  <div><?= $errors->first('title') ?></div>
  endif

  <div>
    <button>Save</button>
  </div>
  <div></div>
</form>
```

If a user tries submitting the form, but encounters a validation error, the following problem occurs:

<div style="padding:56.25% 0 0 0;position:relative;"><iframe src="https://player.vimeo.com/video/844600840?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;" title="morph_problem"></iframe></div><script src="https://player.vimeo.com/api/player.js"></script>

As you can see, when Raxm encounters the new `<div>` for the error message, it doesn't know whether to change the existing `<div>` in-place, or insert the new `<div>` in the middle.

To re-iterate what's happening more explicitly:

- Raxm encounters the first `<div>` in both trees. They are the same, so it continues.
- Raxm encounters the second `<div>` in both trees and thinks they are the same `<div>`, just one has changed contents. So instead of inserting the error message as a new element, it changes the `<button>` into an error message.
- Raxm then, after mistakenly modifying the previous element, notices an additional element at the end of the comparison. It then creates and appends the element after the previous one.
- Therefore destroying, then re-creating an element that otherwise should have been simply moved.

This scenario is at the root of almost all morph-related bugs.

Here are a few specific problematic impacts of these bugs:

- Event listeners and element state are lost between updates
- Event listeners and state are misplaced across the wrong elements
- Entire Raxm components can be reset or duplicated as Raxm components are also simply elements in the DOM tree
- Alpine components and state can be lost or misplaced

Fortunately, Raxm has worked hard to mitigate these problems using the following approaches:

### Internal look-ahead

Raxm has an additional step in its morphing algorithm that checks subsequent elements and their contents before changing an element.

This prevents the above scenario from happening in many cases.

Here is a visualization of the "look-ahead" algorithm in action:

<div style="padding:56.25% 0 0 0;position:relative;"><iframe src="https://player.vimeo.com/video/844600800?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;" title="morph_lookahead"></iframe></div><script src="https://player.vimeo.com/api/player.js"></script>

### Injecting morph markers

On the backend, Raxm automatically detects conditionals inside Blade templates and wraps them in markers that Raxm's JavaScript can use as a guide when morphing.

Here's an example of the previous Blade template but with Raxm's injected markers:

```html
<form axm:submit="save">
  <div>
    <input axm:model="title" />
  </div>

  <!-- __BLOCK__ -->
  <!-- [tl! highlight] -->
  if ($errors->has('title'))
  <div>
    Error:
    <?= $errors->first('title') ?>
  </div>
  endif
  <!-- __ENDBLOCK__ -->
  <!-- [tl! highlight] -->

  <div>
    <button>Save</button>
  </div>
  <div></div>
</form>
```

With these markers injected into the template, Raxm can now more easily detect the difference between a change and an addition.

This feature is extremely beneficial to Raxm applications, but because it requires parsing templates via regex, it can sometimes fail to properly detect conditionals. If this feature is more of a hindrance than a help to your application, you can disable it with the following configuration in your application's `config/raxm.php` file:

```php
'inject_morph_markers' => false,
```

#### Wrapping conditionals

If the above two solutions don't cover your situation, the most reliable way to avoid morphing problems is to wrap conditionals and loops in their own elements that are always present.

For example, here's the above Blade template rewritten with wrapping `<div>` elements:

```html
<form axm:submit="save">
  <div>
    <input axm:model="title" />
  </div>

  <div>
    <!-- [tl! highlight] -->
    if ($errors->has('title'))
    <div><?= $errors->first('title') ?></div>
    endif
  </div>
  <!-- [tl! highlight] -->

  <div>
    <button>Save</button>
  </div>
  <div></div>
</form>
```

Now that the conditional has been wrapped in a persistent element, Raxm will morph the two different HTML trees properly.
