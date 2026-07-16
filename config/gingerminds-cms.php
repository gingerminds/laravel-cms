<?php

declare(strict_types=1);

use Gingerminds\LaravelCms\ApiProvider\Page\PageProvider;
use Gingerminds\LaravelCms\Blocks\Reference\FileReferenceResolver;
use Gingerminds\LaravelCms\Blocks\Reference\MediaReferenceResolver;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuController;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuItemController;
use Gingerminds\LaravelCms\Http\Controllers\Page\PageController;
use Gingerminds\LaravelCms\Http\Controllers\PageCategory\PageCategoryController;
use Gingerminds\LaravelCms\Http\Request\Menu\MenuItemRequest;
use Gingerminds\LaravelCms\Http\Request\Page\PageRequest;
use Gingerminds\LaravelCms\Http\Request\PageCategory\PageCategoryRequest;
use Gingerminds\LaravelCms\Models\Menu\Menu;
use Gingerminds\LaravelCms\Models\Menu\MenuItem\MenuItem;
use Gingerminds\LaravelCms\Models\Page\Page;
use Gingerminds\LaravelCms\Models\Page\PageTranslation;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategory;
use Gingerminds\LaravelCms\Models\PageCategory\PageCategoryTranslation;
use Gingerminds\LaravelCms\Repositories\Menu\MenuItemRepository;
use Gingerminds\LaravelCms\Repositories\Menu\MenuRepository;
use Gingerminds\LaravelCms\ApiProvider\Menu\MenuProvider;
use Gingerminds\LaravelCms\Http\Request\Menu\MenuRequest;
use Gingerminds\LaravelCms\Repositories\Page\PageRepository;
use Gingerminds\LaravelCms\Repositories\PageCategory\PageCategoryRepository;

return [
    'wysiwyg' => [
        'presets' => [
            'default' => [
                'extensions' => ['bold', 'italic', 'underline', 'strike', 'link', 'bulletList', 'orderedList', 'history'],
            ],
            'minimal' => [
                'extensions' => ['bold', 'italic', 'underline', 'link'],
            ],
            'full' => [
                'extensions' => ['bold', 'italic', 'strike', 'underline', 'link', 'bulletList', 'orderedList', 'blockquote', 'heading', 'horizontalRule', 'history'],
            ],
        ],
    ],

    'resources' => [
        'menu' => [
            'model' => Menu::class,
            'controller' => MenuController::class,
            'repository' => MenuRepository::class,
            'provider' => MenuProvider::class,
            'request' => MenuRequest::class,
        ],
        'menu_item' => [
            'model' => MenuItem::class,
            'controller' => MenuItemController::class,
            'repository' => MenuItemRepository::class,
            'request' => MenuItemRequest::class,
        ],
        'page' => [
            'model' => Page::class,
            'controller' => PageController::class,
            'repository' => PageRepository::class,
            'provider' => PageProvider::class,
            'request' => PageRequest::class,
        ],
        'page_translation' => [
            'model' => PageTranslation::class,
        ],
        'page_category' => [
            'model' => PageCategory::class,
            'controller' => PageCategoryController::class,
            'repository' => PageCategoryRepository::class,
            'request' => PageCategoryRequest::class,
        ],
        'page_category_translation' => [
            'model' => PageCategoryTranslation::class,
        ],
        'page_block' => [
            'controller' => \Gingerminds\LaravelCms\Http\Controllers\Page\PageBlockController::class,
        ],
    ],

    // Content blocks (see docs/Blocks.md). Merged additively at boot time in
    // LaravelCmsServiceProvider so a project publishing this config only
    // needs to list what it *adds* — the package's own entries are never
    // silently dropped by Laravel's shallow mergeConfigFrom().
    'block_paths' => [
        [
            'path' => dirname(__DIR__) . '/src/Blocks/Type',
            'namespace' => 'Gingerminds\\LaravelCms\\Blocks\\Type\\',
        ],
        [
            'path' => app_path('Cms/Blocks'),
            'namespace' => 'App\\Cms\\Blocks\\',
        ],
    ],

    // Override an existing block class by key: 'title_text' => \App\Cms\Blocks\TitleText::class.
    'blocks' => [],

    // Block keys hidden from the catalog (step 1 of the add-block modal).
    // Existing pages using a disabled block still render/validate fine.
    'disabled_blocks' => [],

    // Catalog sort weight override, without subclassing: 'title_text' => 5.
    'block_order' => [],

    // Reference field resolvers (ContentReferenceResolver, docs/Blocks.md
    // "API"): field `type` => FQCN implementing `ReferenceFieldResolver`.
    // Merged additively like `block_paths` above — a project adds its own
    // reference field type (e.g. a field pointing to one of its own
    // models) without losing `file`/`media`.
    'reference_resolvers' => [
        'file'  => FileReferenceResolver::class,
        'media' => MediaReferenceResolver::class,
    ],
];
