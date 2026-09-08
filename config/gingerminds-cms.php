<?php

declare(strict_types=1);

use Gingerminds\LaravelCms\ApiProvider\Page\PageProvider;
use Gingerminds\LaravelCms\ApiProvider\Search\SearchProvider;
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
use Gingerminds\LaravelCms\Repositories\Search\SearchRepository;
use Gingerminds\LaravelCms\Models\Search\SearchIndex;
use Gingerminds\LaravelCms\ApiProvider\Search\SearchResultProvider;

return [
    'wysiwyg' => [
        'presets' => [
            'default' => [
                'extensions' => ['bold', 'italic', 'underline', 'strike', 'link', 'bulletList', 'orderedList', 'history'],
            ],
            'extended' => [
                'extensions' => ['bold', 'italic', 'underline', 'strike', 'link', 'bulletList', 'orderedList', 'table', 'history'],
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
        'search' => [
            'model' => SearchIndex::class,
            'repository' => SearchRepository::class,
            'provider' => SearchProvider::class,
        ],
        'search_result' => [
            'model' => SearchIndex::class,
            'repository' => SearchRepository::class,
            'provider' => SearchResultProvider::class,
        ],
    ],

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

    'blocks' => [],

    'disabled_blocks' => [],

    'block_order' => [],

    'slug_overwrite' => [
        'create' => true,
        'edit' => false,
    ],

    'reference_resolvers' => [
        'file'  => FileReferenceResolver::class,
        'media' => MediaReferenceResolver::class,
    ],

    'search_resources' => [
        'page' => [
            'model' => Page::class,
            'label' => 'Pages',
            'category_relation' => 'category',
            'category_model' => PageCategory::class,
            'group' => Page::GROUP_LIST,
        ],
    ],
];
