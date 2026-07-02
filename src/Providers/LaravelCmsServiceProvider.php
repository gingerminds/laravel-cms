<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Providers;

use ApiPlatform\State\ProviderInterface;
use Gingerminds\LaravelCms\ApiProvider\Menu\MenuProvider;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuController;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuItemController;
use Gingerminds\LaravelCms\Http\Request\Menu\MenuItemRequest;
use Gingerminds\LaravelCms\Http\Request\Menu\MenuRequest;
use Gingerminds\LaravelCms\Models\Menu\Menu;
use Gingerminds\LaravelCms\Models\Menu\MenuItem\MenuItem;
use Gingerminds\LaravelCms\Repositories\Menu\MenuItemRepository;
use Gingerminds\LaravelCms\Repositories\Menu\MenuRepository;
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

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/gingerminds-cms.php',
            'gingerminds-cms'
        );

        $this->tagClassesFromPath(
            __DIR__ . '/../ApiProvider',
            'Gingerminds\\LaravelCms\\ApiProvider\\',
            ProviderInterface::class
        );
    }

    public function boot(): void
    {
        Route::model('menu', ResourceResolver::model('menu'));

        Route::model('menu_item', ResourceResolver::model('menu_item'));

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
}
