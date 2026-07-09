<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Providers;

use ApiPlatform\State\ProviderInterface;
use Gingerminds\LaravelCms\ApiProvider\Menu\MenuProvider;
use Gingerminds\LaravelCms\ApiProvider\Page\PageProvider;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuController;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuItemController;
use Gingerminds\LaravelCms\Http\Controllers\Page\PageController;
use Gingerminds\LaravelCms\Http\Controllers\PageCategory\PageCategoryController;
use Gingerminds\LaravelCms\Http\Middleware\Api\InjectPageFiltersMiddleware;
use Gingerminds\LaravelCms\Http\Request\Menu\MenuItemRequest;
use Gingerminds\LaravelCms\Http\Request\Menu\MenuRequest;
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
use Gingerminds\LaravelCms\Repositories\Page\PageRepository;
use Gingerminds\LaravelCms\Repositories\PageCategory\PageCategoryRepository;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LaravelCmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(LaravelCmsAuthServiceProvider::class);

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/gingerminds-cms.php',
            'gingerminds-cms'
        );

        $this->bindResources();

        $this->tagClassesFromPath(
            __DIR__ . '/../ApiProvider',
            'Gingerminds\\LaravelCms\\ApiProvider\\',
            ProviderInterface::class
        );

        // Le package s'enregistre lui-même auprès d'api-platform : le projet
        // consommateur n'a rien à ajouter dans son config/api-platform.php.
        // Fait dans register() (et non boot()) : tous les register() tournent
        // avant tous les boot(), donc cette valeur est garantie disponible
        // avant que le provider api-platform ne construise ses routes dans
        // son propre boot(), quel que soit l'ordre de boot entre packages.
        config([
            'api-platform.routes.middleware' => array_values(array_unique(array_merge(
                config('api-platform.routes.middleware', []),
                [InjectPageFiltersMiddleware::class]
            ))),
        ]);
    }

    public function boot(): void
    {
        Route::model('menu', ResourceResolver::model('menu'));

        Route::model('menu_item', ResourceResolver::model('menu_item'));

        Route::model('page', ResourceResolver::model('page'));

        Route::model('page_category', ResourceResolver::model('page_category'));

        // Chargement des routes du package
        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        }

        // Chargement des migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Chargement des vues
        $this->loadViewsFrom(
            __DIR__ . '/../../resources/views',
            'gingerminds-cms'
        );

        // Chargement des traductions
        $this->loadTranslationsFrom(
            __DIR__ . '/../../resources/lang',
            'gingerminds-cms'
        );

        // Publication de la config
        $this->publishes([
            __DIR__ . '/../../config/gingerminds-cms.php' => config_path('gingerminds-cms.php'),
        ], 'gingerminds-cms-config');

        // Publication des assets JS (pas de SCSS dans ce package)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../resources/js' => resource_path('js/vendor/gingerminds-cms'),
            ], 'gingerminds-assets');
        }
    }

    private function tagClassesFromPath(string $path, string $namespace, string $interface): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $toTag    = [];

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($path) + 1, -4);
            $class    = $namespace . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (class_exists($class) && is_subclass_of($class, $interface)) {
                $toTag[] = $class;
            }
        }

        if ($toTag !== []) {
            $this->app->tag($toTag, $interface);
        }
    }

    private function bindResources(): void
    {
        $this->app->bind(
            MenuController::class,
            ResourceResolver::controller('menu')
        );
        $this->app->bind(
            MenuRepository::class,
            ResourceResolver::repository('menu')
        );
        $this->app->bind(
            Menu::class,
            ResourceResolver::model('menu')
        );
        $this->app->bind(
            MenuProvider::class,
            ResourceResolver::provider('menu')
        );
        $this->app->bind(
            MenuRequest::class,
            ResourceResolver::request('menu')
        );

        $this->app->bind(
            MenuItemController::class,
            ResourceResolver::controller('menu_item')
        );
        $this->app->bind(
            MenuItemRepository::class,
            ResourceResolver::repository('menu_item')
        );
        $this->app->bind(
            MenuItem::class,
            ResourceResolver::model('menu_item')
        );
        $this->app->bind(
            MenuItemRequest::class,
            ResourceResolver::request('menu_item')
        );

        $this->app->bind(
            PageController::class,
            ResourceResolver::controller('page')
        );
        $this->app->bind(
            PageRepository::class,
            ResourceResolver::repository('page')
        );
        $this->app->bind(
            Page::class,
            ResourceResolver::model('page')
        );
        $this->app->bind(
            PageProvider::class,
            ResourceResolver::provider('page')
        );
        $this->app->bind(
            PageRequest::class,
            ResourceResolver::request('page')
        );

        $this->app->bind(
            PageTranslation::class,
            ResourceResolver::model('page_translation')
        );

        $this->app->bind(
            PageCategoryController::class,
            ResourceResolver::controller('page_category')
        );
        $this->app->bind(
            PageCategoryRepository::class,
            ResourceResolver::repository('page_category')
        );
        $this->app->bind(
            PageCategory::class,
            ResourceResolver::model('page_category')
        );
        $this->app->bind(
            PageCategoryRequest::class,
            ResourceResolver::request('page_category')
        );

        $this->app->bind(
            PageCategoryTranslation::class,
            ResourceResolver::model('page_category_translation')
        );
    }
}
