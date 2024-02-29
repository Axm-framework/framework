- The component
  - Counter component
- Rendering the component
  - Mount
    - New up class
    - Dehydrate state
    - Embed inside HTML
    - Return HTML
- Initializing the component in JS
  - Finding axm:id elements
  - Extracting id and snapshot
  - Newing up object
- Sending an update
  - Registering event listeners
  - Sending a fetch request with updates and snapshot
- Receiving an update
  - Converting snapshot to component (hydrate)
  - Applying updates
  - Rendering component
  - Returning HTML and new snapshot
- Processing an update
  - Replacing with new snapshot
  - Replacing HTML with new HTML
    - Morphing

## The component

```php
<?php

use App\Raxm;

class Counter extends Component
{
    public $count = 1;

    public function increment()
    {
        $this->count++;
    }

    public function render()
    {
        return view('raxm.counter');
    }
}
```

```html
<div>
  <button axm:click="increment">Increment</button>

  <span><?= $count ?></span>
</div>
```

## Rendering the component

```html
<raxm:counter />
```

```php
<?php echo Raxm::mount('counter'); ?>
```

```php
public function mount($name)
{
    $class = Raxm::getComponentClassByName();

    $component = new $class;

    $id = str()->random(20);

    $component->setId($id);

    $data = $component->getData();

    $view = $component->render();

    $html = $view->render($data);

    $snapshot = [
        'data' => $data,
        'memo' => [
            'id' => $component->getId(),
            'name' => $component->getName(),
        ]
    ];

    return Raxm::embedSnapshotInsideHtml($html, $snapshot);
}
```

```html
<div
  axm:id="123456789"
  axm:snapshot="{ data: { count: 0 }, memo: { 'id': '123456789', 'name': 'counter' }"
>
  <button axm:click="increment">Increment</button>

  <span>1</span>
</div>
```

## JavaScript initialization

```js
let el = document.querySelector('wire\\:id')

let id = el.getAttribute('axm:id')
let jsonSnapshot = el.getAttribute('axm:snapshot')
let snapshot = JSON.parse(jsonSnapshot)

let component = { id, snapshot }

walk(el, el => {
    el.hasAttribute('axm:click') {
        let action = el.getAttribute('axm:click')

        el.addEventListener('click', e => {
            updateComponent(el, component, action)
        })
    }
})

function updateComponent(el, component, action) {
    let response fetch('/raxm/update', {
        body: JSON.stringify({
            "snapshot": snapshot,
            "calls": [
                ["method": action, "params": []],
            ]
        })
    })

    // To be continued...
}
```

## Receiving an update

```php
Route::post('/raxm/update', function () {
    $snapshot = request('snapshot');
    $calls = requets('calls');

    $component = Raxm::fromSnapshot($snapshot);

    foreach ($calls as $call) {
        $component->{$call['method']}(...$call['params']);
    }

    [$html, $snapshot] = Raxm::snapshot($component);

    return [
        'snapshot' => $snapshot,
        'html' => $html,
    ];
});
```

## Handling an update

```js
function updateComponent(el, component, action) {
  fetch("/raxm/update", {
    body: JSON.stringify({
      snapshot: snapshot,
      calls: [[("method": action), ("params": [])]],
    }),
  })
    .then((i) => i.json())
    .then((response) => {
      let { html, snapshot } = response;

      component.snapshot = snapshot;

      el.outerHTML = html;
    });
}
```
