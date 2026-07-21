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

Each preset is a named list of TipTap extensions to enable, which controls both which toolbar buttons are shown and which formatting the editor accepts. Available extension names: `bold`, `italic`, `underline`, `strike`, `link`, `bulletList`, `orderedList`, `heading`, `blockquote`, `horizontalRule`, `table`, `history`. Used via the `preset` prop of the [WYSIWYG component](./Components.md). Add your own presets freely — for example, a project that only ever needs bold text might add:

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
    'page' => [
        'model'      => Page::class,
        'controller' => PageController::class,
        'repository' => PageRepository::class,
        'provider'   => PageProvider::class,
        'request'    => PageRequest::class,
    ],
    'page_translation' => [
        'model' => PageTranslation::class,
    ],
    'page_category' => [
        'model'      => PageCategory::class,
        'controller' => PageCategoryController::class,
        'repository' => PageCategoryRepository::class,
        'request'    => PageCategoryRequest::class,
    ],
    'page_category_translation' => [
        'model' => PageCategoryTranslation::class,
    ],
],
```

Read by `Gingerminds\LaravelCms\Resolver\ResourceResolver`, the same class-binding-override pattern used throughout the other Gingerminds packages. `menu_item`, `page_translation` and `page_category_translation` have no `provider`/`controller`/`request` entries: translation models have no controller of their own (edited inline through their parent's form), and `MenuItem` has no directly callable API operations (see [API](./API.md)). `page_category` has no `provider` — `PageCategory` isn't an API resource at all (see [Pages](./Pages.md)).

> **Project convention:** if your app needs to customize any of these (a controller, request, or model), don't edit the package's own config or classes — override the entry in your app's *published* `config/gingerminds-cms.php` to point at your own class instead (make sure to add the matching `use` import at the top of that file).
