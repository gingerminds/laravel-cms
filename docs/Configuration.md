# Configuration

Published to `config/gingerminds-cms.php` (see [Installation](./Installation.md)).

## `wysiwyg.presets`

```php
'wysiwyg' => [
    'presets' => [
        'default' => ['extensions' => ['bold', 'italic', 'underline', 'strike', 'link', 'bulletList', 'orderedList', 'history']],
        'minimal' => ['extensions' => ['bold', 'italic', 'underline', 'link']],
        'full'    => ['extensions' => ['bold', 'italic', 'strike', 'underline', 'link', 'bulletList', 'orderedList', 'blockquote', 'heading', 'horizontalRule', 'history']],
    ],
],
```

Each preset is a named list of TipTap extensions to enable, which controls both which toolbar buttons are shown and which formatting the editor accepts. Used via the `preset` prop of the [WYSIWYG component](./Components.md). Add your own presets freely — for example, a project that only ever needs bold text might add:

```php
'presets' => [
    // ...
    'only_bold' => ['extensions' => ['bold']],
],
```

## `resources`

```php
'resources' => [
    'menu' => [
        'model'      => Menu::class,
        'controller' => MenuController::class,
        'repository' => MenuRepository::class,
        'provider'   => MenuProvider::class,
        'request'    => MenuRequest::class,
    ],
    'menu_item' => [
        'model'      => MenuItem::class,
        'controller' => MenuItemController::class,
        'repository' => MenuItemRepository::class,
        'request'    => MenuItemRequest::class,
    ],
],
```

Read by `Gingerminds\LaravelCms\Resolver\ResourceResolver`, the same class-binding-override pattern used throughout the other Gingerminds packages. `menu_item` has no `provider` entry since `MenuItem` has no directly callable API operations (see [API](./API.md)).
