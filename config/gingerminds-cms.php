<?php

declare(strict_types=1);

use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuController;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuItemController;
use Gingerminds\LaravelCms\Http\Request\Menu\MenuItemRequest;
use Gingerminds\LaravelCms\Models\Menu\Menu;
use Gingerminds\LaravelCms\Models\Menu\MenuItem\MenuItem;
use Gingerminds\LaravelCms\Repositories\Menu\MenuItemRepository;
use Gingerminds\LaravelCms\Repositories\Menu\MenuRepository;
use Gingerminds\LaravelCms\ApiProvider\Menu\MenuProvider;
use Gingerminds\LaravelCms\Http\Request\Menu\MenuRequest;

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
    ],
];
