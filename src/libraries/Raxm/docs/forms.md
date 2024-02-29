Because forms are the backbone of most web applications, Raxm provides loads of helpful utilities for building them. From handling simple input elements to complex things like real-time validation or file uploading, Raxm has simple, well-documented tools to make your life easier and delight your users.

Let's dive in.

## Submitting a form

Let's start by looking at a very simple form in a `CreatePost` component. This form will have two simple text inputs and a submit button, as well as some code on the backend to manage the form's state and submission:

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
        Post::create(
            $this->only(['title', 'content'])
        );

        return $this->redirect('/posts')
            ->with('status', 'Post successfully created.');
    }

    public function render()
    {
        return view('raxm.create-post');
    }
}
```

```html
<form axm:submit="save">
  <input type="text" axm:model="title" />

  <input type="text" axm:model="content" />

  <button type="submit">Save</button>
</form>
```

As you can see, we are "binding" the public `$title` and `$content` properties in the form above using `axm:model`. This is one of the most commonly used and powerful features of Raxm.

In addition to binding `$title` and `$content`, we are using `axm:submit` to capture the `submit` event when the "Save" button is clicked and invoking the `save()` action. This action will persist the form input to the database.

After the new post is created in the database, we redirect the user to the `ShowPosts` component page and show them a "flash" message that the new post was created.

### Adding validation

To avoid storing incomplete or dangerous user input, most forms need some sort of input validation.

Raxm makes validating your forms as simple as adding `#[Rule]` attributes above the properties you want to be validated.

Once a property has a `#[Rule]` attribute attached to it, the validation rule will be applied to the property's value any time it's updated server-side.

Let's add some basic validation rules to the `$title` and `$content` properties in our `CreatePost` component:

```php
<?php

namespace App\Raxm;

use Raxm\Attributes\Rule; // [tl! highlight]
use App\Raxm;
use App\Models\Post;

class CreatePost extends Component
{
	#[Rule('required')] // [tl! highlight]
	public $title = '';

	#[Rule('required')] // [tl! highlight]
	public $content = '';

	public function save()
	{
		$this->validate(); // [tl! highlight]

		Post::create(
			$this->only(['title', 'content'])
		);

		return $this->redirect('/posts');
	}

	public function render()
	{
		return view('raxm.create-post');
	}
}
```

We'll also modify our Blade template to show any validation errors on the page.

```html
<form axm:submit="save">
	<input type="text" axm:model="title" />
	<div>
		<span class="error"> <?= error('title', $messages) ?> </span>
		<!-- [tl! highlight] -->
	</div>

	<input type="text" axm:model="content" />
	<div>
		<span class="error"> <?= error('content', $messages) ?> </span>
		<!-- [tl! highlight] -->
	</div>

	<button type="submit">Save</button>
</form>
```

Now, if the user tries to submit the form without filling in any of the fields, they will see validation messages telling them which fields are required before saving the post.

Raxm has a lot more validation features to offer. For more information, visit our [dedicated documentation page on Validation](/docs/validation).

### Extracting a form object

If you are working with a large form and prefer to extract all of its properties, validation logic, etc., into a separate class, Raxm offers form objects.

Form objects allow you to re-use form logic across components and provide a nice way to keep your component class cleaner by grouping all form-related code into a separate class.

You can either create a form class by hand or use the convenient axm command:

```shell
php axm raxm:form PostForm
```

The above command will create a file called `app/Raxm/Forms/PostForm.php`.

Let's rewrite the `CreatePost` component to use a `PostForm` class:

```php
<?php

namespace App\Raxm\Forms;

use Raxm\Attributes\Rule;
use Raxm\Form;

class PostForm extends Form
{
    #[Rule('required|min:5')]
    public $title = '';

    #[Rule('required|min:5')]
    public $content = '';
}
```

```php
<?php

namespace App\Raxm;

use App\Raxm;
use App\Models\Post;
use App\Raxm\Forms\PostForm;

class CreatePost extends Component
{
    public PostForm $form;

    public function save()
    {
        $this->validate();

        Post::create(
            $this->form->all()
        );

        return $this->redirect('/posts');
    }

    public function render()
    {
        return view('raxm.create-post');
    }
}
```

```html
<form axm:submit="save">
	<input type="text" axm:model="form.title" />
	<div>
		<span class="error"> <?= error('form.title', $messages) ?> </span>
	</div>

	<input type="text" axm:model="form.content" />
	<div>
		<span class="error"> <?= error('form.title', $messages) ?> </span>
	</div>

	<button type="submit">Save</button>
</form>
```

If you'd like, you can also extract the post creation logic into the form object like so:

```php
<?php

namespace App\Raxm\Forms;

use Raxm\Attributes\Rule;
use Raxm\Form;
use App\Models\Post;

class PostForm extends Form
{
    #[Rule('required|min:5')]
    public $title = '';

    #[Rule('required|min:5')]
    public $content = '';

    public function store()
    {
        Post::create($this->all());
    }
}
```

Now you can call `$this->form->store()` from the component:

```php
class CreatePost extends Component
{
    public PostForm $form;

    public function save()
    {
        $this->form->store();

        return $this->redirect('/posts');
    }

    // ...
}
```

If you want to use this form object for both a create and update form, you can easily adapt it to handle both use cases.

Here's what it would look like to use this same form object for an `UpdatePost` component and fill it with initial data:

```php
<?php

namespace App\Raxm;

use App\Raxm;
use App\Raxm\Forms\PostForm;
use App\Models\Post;

class UpdatePost extends Component
{
    public PostForm $form;

    public function mount(Post $post)
    {
        $this->form->setPost($post);
    }

    public function save()
    {
        $this->form->update();

        return $this->redirect('/posts');
    }

    public function render()
    {
        return view('raxm.create-post');
    }
}
```

```php
<?php

namespace App\Raxm\Forms;

use Raxm\Attributes\Rule;
use Raxm\Form;
use App\Models\Post;

class PostForm extends Form
{
    public ?Post $post;

    #[Rule('required|min:5')]
    public $title = '';

    #[Rule('required|min:5')]
    public $content = '';

    public function setPost(Post $post)
    {
        $this->post = $post;

        $this->title = $post->title;

        $this->content = $post->content;
    }

    public function store()
    {
        Post::create($this->only(['title', 'content']));
    }

    public function update()
    {
        $this->post->update(
            $this->all()
        );
    }
}
```

As you can see, we've added a `setPost()` method to the `PostForm` object to optionally allow for filling the form with existing data as well as storing the post on the form object for later use. We've also added an `update()` method for updating the existing post.

Form objects are not required when working with Raxm, but they do offer a nice abstraction for keeping your components free of repetitive boilerplate.

### Resetting form fields

If you are using a form object, you may want to reset the form after it has been submitted. This can be done by calling the `reset()` method:

```php
<?php

namespace App\Raxm\Forms;

use Raxm\Attributes\Rule;
use App\Models\Post;
use Raxm\Form;

class PostForm extends Form
{
    #[Rule('required|min:5')]
    public $title = '';

    #[Rule('required|min:5')]
    public $content = '';

    // ...

    public function store()
    {
        Post::create($this->all());

        $this->reset(); // [tl! highlight]
    }
}
```

You can also reset specific properties by passing the property names into the `reset()` method:

```php
$this->reset('title');

// Or multiple at once...

$this->reset('title', 'content');
```

### Showing a loading indicator

By default, Raxm will automatically disable submit buttons and mark inputs as `readonly` while a form is being submitted, preventing the user from submitting the form again while the first submission is being handled.

However, it can be difficult for users to detect this "loading" state without extra affordances in your application's UI.

Here's an example of adding a small loading spinner to the "Save" button via `axm:loading` so that a user understands that the form is being submitted:

```html
<button type="submit">
	Save

	<div axm:loading>
		<svg>...</svg>
		<!-- SVG loading spinner -->
	</div>
</button>
```

Now, when a user presses "Save", a small, inline spinner will show up.

Raxm's `axm:loading` feature has a lot more to offer. Visit the [Loading documentation to learn more.](/docs/loading)

## Live-updating fields

By default, Raxm only sends a network request when the form is submitted (or any other [action](/docs/actions) is called), not while the form is being filled out.

Take the `CreatePost` component, for example. If you want to make sure the "title" input field is synchronized with the `$title` property on the backend as the user types, you may add the `.live` modifier to `axm:model` like so:

```html
<input type="text" axm:model.live="title" />
```

Now, as a user types into this field, network requests will be sent to the server to update `$title`. This is useful for things like a real-time search, where a dataset is filtered as a user types into a search box.

## Only updating fields on _blur_

For most cases, `axm:model.live` is fine for real-time form field updating; however, it can be overly network resource-intensive on text inputs.

If instead of sending network requests as a user types, you want to instead only send the request when a user "tabs" out of the text input (also referred to as "blurring" an input), you can use the `.blur` modifier instead:

```html
<input type="text" axm:model.blur="title" />
```

Now the component class on the server won't be updated until the user presses tab or clicks away from the text input.

## Real-time validation

Sometimes, you may want to show validation errors as the user fills out the form. This way, they are alerted early that something is wrong instead of having to wait until the entire form is filled out.

Raxm handles this sort of thing automatically. By using `.live` or `.blur` on `axm:model`, Raxm will send network requests as the user fills out the form. Each of those network requests will run the appropriate validation rules before updating each property. If validation fails, the property won't be updated on the server and a validation message will be shown to the user:

```html
<input type="text" axm:model.blur="title" />

<div>
	<span class="error"> <?= error('title', $messages) ?> </span>

</div>
```

```php
#[Rule('required|min:5')]
public $title = '';
```

Now, if the user only types three characters into the "title" input, then clicks on the next input in the form, a validation message will be shown to them indicating there is a five character minimum for that field.

For more information, check out the [validation documentation page](/docs/validation).

## Real-time form saving

If you want to automatically save a form as the user fills it out rather than wait until the user clicks "submit", you can do so using Raxm's `updated()` hook:

```php
<?php

namespace App\Raxm;

use Raxm\Attributes\Rule;
use App\Raxm;
use App\Models\Post;

class UpdatePost extends Component
{
    public Post $post;

    #[Rule('required')]
    public $title = '';

    #[Rule('required')]
    public $content = '';

    public function mount(Post $post)
    {
        $this->post = $post;
    }

    public function updated($name, $value)
    {
        $this->post->update([
            $name => $value,
        ]);
    }

    public function render()
    {
        return view('raxm.create-post');
    }
}
```

```html
<form axm:submit>
	<input type="text" axm:model.blur="title" />
	<div>
		<span class="error"> <?= error('title', $messages) ?> </span>

	</div>

	<input type="text" axm:model.blur="content" />
	<div>
		<span class="error"> <?= error('content', $messages) ?> </span>

	</div>
</form>
```

In the above example, when a user completes a field (by clicking or tabbing to the next field), a network request is sent to update that property on the component. Immediately after the property is updated on the class, the `updated()` hook is called for that specific property name and its new value.

We can use this hook to update only that specific field in the database.

Additionally, because we have the `#[Rule]` attributes attached to those properties, the validation rules will be run before the property is updated and the `updated()` hook is called.

To learn more about the "updated" lifecycle hook and other hooks, [visit the lifecycle hooks documentation](/docs/lifecycle-hooks).

## Showing dirty indicators

In the real-time saving scenario discussed above, it may be helpful to indicate to users when a field hasn't been persisted to the database yet.

For example, if a user visits an `UpdatePost` page and starts modifying the title of the post in a text input, it may be unclear to them when the title is actually being updated in the database, especially if there is no "Save" button at the bottom of the form.

Raxm provides the `axm:dirty` directive to allow you to toggle elements or modify classes when an input's value diverges from the server-side component:

```html
<input type="text" axm:model.blur="title" axm:dirty.class="border-yellow" />
```

In the above example, when a user types into the input field, a yellow border will appear around the field. When the user tabs away, the network request is sent and the border will disappear; signaling to them that the input has been persisted and is no longer "dirty".

If you want to toggle an entire element's visibility, you can do so by using `axm:dirty` in conjunction with `axm:target`. `axm:target` is used to specify which piece of data you want to watch for "dirtiness". In this case, the "title" field:

```html
<input type="text" axm:model="title" />

<div axm:dirty axm:target="title">Unsaved...</div>
```

## Debouncing input

When using `.live` on a text input, you may want more fine-grained control over how often a network request is sent. By default, a debounce of "250ms" is applied to the input; however, you can customize this using the `.debounce` modifier:

```html
<input type="text" axm:model.live.debounce.150ms="title" />
```

Now that `.debounce.150ms` has been added to the field, a shorter debounce of "150ms" will be used when handling input updates for this field. In other words, as a user types, a network request will only be sent if the user stops typing for at least 150 milliseconds.

## Throttling input

As stated previously, when an input debounce is applied to a field, a network request will not be sent until the user has stopped typing for a certain amount of time. This means if the user continues typing a long message, a network request won't be sent until the user is finished.

Sometimes this isn't the desired behavior, and you would rather send a request as the user types, not when they've finished or taken a break.

In these cases, you can instead use `.throttle` to signify a time interval to send network requests:

```html
<input type="text" axm:model.live.throttle.150ms="title" />
```

In the above example, as a user is typing continuously in the "title" field, a network request will be sent every 150 milliseconds until the user is finished.

## Extracting input fields to Blade components

Even in a small component such as the `CreatePost` example we've been discussing, we end up duplicating lots of form field boilerplate like validation messages and labels.

It can be helpful to extract repetitive UI elements such as these into dedicated [Blade components](https://laravel.com/docs/blade#components) to be shared across your application.

For example, below is the original Blade template from the `CreatePost` component. We will be extracting the following two text inputs into dedicated Blade components:

```html
<form axm:submit="save">
	<input type="text" axm:model="title" />
	<!-- [tl! highlight:3] -->
	<div>
		<span class="error"> <?= error('title', $messages) ?> </span>

	</div>

	<input type="text" axm:model="content" />
	<!-- [tl! highlight:3] -->
	<div>
		<span class="error"> <?= error('content', $messages) ?> </span>

	</div>

	<button type="submit">Save</button>
</form>
```

## Input fields

Raxm supports most native input elements out of the box. Meaning you should just be able to attach `axm:model` to any input element in the browser and easily bind properties to them.

Here's a comprehensive list of the different available input types and how you use them in a Raxm context.

### Text inputs

First and foremost, text inputs are the bedrock of most forms. Here's how to bind a property named "title" to one:

```html
<input type="text" axm:model="title" />
```

### Textarea inputs

Textarea elements are similarly straightforward. Simply add `axm:model` to a textarea and the value will be bound:

```html
<textarea type="text" axm:model="content"></textarea>
```

If the "content" value is initialized with a string, Raxm will fill the textarea with that value - there's no need to do something like the following:

```html
<!-- Warning: This snippet demonstrates what NOT to do... -->

<textarea type="text" axm:model="content"><?= $content ?></textarea>
```

### Checkboxes

Checkboxes can be used for single values, such as when toggling a boolean property. Or, checkboxes may be used to toggle a single value in a group of related values. We'll discuss both scenarios:

#### Single checkbox

At the end of a signup form, you might have a checkbox allowing the user to opt-in to email updates. You might call this property `$receiveUpdates`. You can easily bind this value to the checkbox using `axm:model`:

```html
<input type="checkbox" axm:model="receiveUpdates" />
```

Now when the `$receiveUpdates` value is `false`, the checkbox will be unchecked. Of course, when the value is `true`, the checkbox will be checked.

#### Multiple checkboxes

Now, let's say in addition to allowing the user to decide to receive updates, you have an array property in your class called `$updateTypes`, allowing the user to choose from a variety of update types:

```php
public $updateTypes = [];
```

By binding multiple checkboxes to the `$updateTypes` property, the user can select multiple update types and they will be added to the `$updateTypes` array property:

```html
<input type="checkbox" value="email" axm:model="updateTypes" />
<input type="checkbox" value="sms" axm:model="updateTypes" />
<input type="checkbox" value="notificaiton" axm:model="updateTypes" />
```

For example, if the user checks the first two boxes but not the third, the value of `$updateTypes` will be: `["email", "sms"]`

### Radio buttons

To toggle between two different values for a single property, you may use radio buttons:

```html
<input type="radio" value="yes" axm:model="receiveUpdates" />
<input type="radio" value="no" axm:model="receiveUpdates" />
```

### Select dropdowns

Raxm makes it simple to work with `<select>` dropdowns. When adding `axm:model` to a dropdown, the currently selected value will be bound to the provided property name and vice versa.

In addition, there's no need to manually add `selected` to the option that will be selected - Raxm handles that for you automatically.

Below is an example of a select dropdown filled with a static list of states:

```html
<select axm:model="state">
	<option value="AL">Alabama</option>
	<option></option>
	<option value="AK">Alaska</option>
	<option value="AZ">Arizona</option>
	...
</select>
```

When a specific state is selected, for example, "Alaska", the `$state` property on the component will be set to `AK`. If you would prefer the value to be set to "Alaska" instead of "AK", you can leave the `value=""` attribute off the `<option>` element entirely.

Often, you may build your dropdown options dynamically using Blade:

```html
<select axm:model="state">
	<?php foreach (\App\Models\State::all() as $state)
	<option value="<?= $state->id ?>"><?= $state->label ?></option>
	endforeach ?>
</select>
```

If you don't have a specific option selected by default, you may want to show a muted placeholder option by default, such as "Select a state":

```html
<select axm:model="state">
  <option disabled>Select a state...</option>

  foreach (\App\Models\State::all() as $state)
	<option value="<?= $state->id ?>"><?= $state->label ?></option>
  endforeach
</select>
```

As you can see, there is no "placeholder" attribute for a select menu like there is for text inputs. Instead, you have to add a `disabled` option element as the first option in the list.

### Multi-select dropdowns

If you are using a "multiple" select menu, Raxm works as expected. In this example, states will be added to the `$states` array property when they are selected and removed if they are deselected:

```html
<select axm:model="states" multiple>
	<option value="AL">Alabama</option>
	<option></option>
	<option value="AK">Alaska</option>
	<option value="AZ">Arizona</option>
	...
</select>
```
