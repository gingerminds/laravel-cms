<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Providers;

use ApiPlatform\State\ProviderInterface;
use Gingerminds\LaravelCms\ApiProvider\Menu\MenuProvider;
use Gingerminds\LaravelCms\ApiProvider\Page\PageProvider;
use Gingerminds\LaravelCms\Console\Commands\Make\CreateBlock;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuController;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuItemController;
use Gingerminds\LaravelCms\Http\Controllers\Page\PageBlockController;
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

        $this->mergeAdditiveArrayConfig('disabled_blocks');
        $this->mergeAdditiveArrayConfig('block_paths', 'path');
        $this->mergeAdditiveArrayConfig('reference_resolvers', associative: true);

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

        // Publication des assets JS/SCSS, même tag et même convention que
        // laravel-core et laravel-media-manager : chaque package publie ses
        // resources/js et resources/scss sous js|scss/vendor/<package>, le
        // projet consommateur important lui-même vendor/gingerminds-cms/app
        // dans son propre resources/scss/app.scss.
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../resources/scss' => resource_path('scss/vendor/gingerminds-cms'),
                __DIR__ . '/../../resources/js'   => resource_path('js/vendor/gingerminds-cms'),
            ], 'gingerminds-assets');

            // Lets a project customize the block/preview stubs used by
            // `make:cms-block` the same way `gingerminds-core` publishes
            // its own (checked first by CreateBlock::stub()).
            $this->publishes([
                __DIR__ . '/../../stubs' => base_path('stubs/vendor/gingerminds-cms'),
            ], 'gingerminds-stubs');

            $this->commands([
                CreateBlock::class,
            ]);
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

        $this->app->bind(
            PageBlockController::class,
            ResourceResolver::controller('page_block')
        );
    }

    /**
     * `mergeConfigFrom()` only merges shallowly: a top-level array key
     * present in a project's published config completely replaces the
     * package's default for that key, instead of extending it (see
     * docs/Blocks.md). Re-merges one array key on top of that, combining
     * the package's own default entries with whatever the project's
     * published config now holds — additive, not "either/or".
     *
     * Three shapes are supported, one per existing config key: a flat list
     * of scalars deduplicated as a set (`disabled_blocks`), a list of
     * assoc arrays deduplicated by one of their columns (`block_paths`,
     * `$uniqueBy: 'path'`), and a plain keyed map where a published key
     * simply overrides the package's default for that key
     * (`reference_resolvers`, `$associative: true` — `array_merge()`
     * already does exactly that, no dedup/reindex needed/wanted since string
     * keys aren't reindexed by it).
     */
    private function mergeAdditiveArrayConfig(string $key, ?string $uniqueBy = null, bool $associative = false): void
    {
        $packageDefaults = require __DIR__ . '/../../config/gingerminds-cms.php';
        $default         = $packageDefaults[$key] ?? [];
        $published       = config("gingerminds-cms.{$key}", []);

        $merged = array_merge($default, $published);

        if ($associative) {
            // Nothing further to do: array_merge() above already keeps
            // string keys and lets $published win on collision.
        } elseif ($uniqueBy !== null) {
            // array_column()'s result is already a re-indexed list when no
            // $index_key is given (the inner call here) — wrapping it in
            // array_values() again would be a no-op.
            $merged = array_column(
                array_column($merged, null, $uniqueBy),
                null
            );
        } else {
            $merged = array_values(array_unique($merged));
        }

        config(["gingerminds-cms.{$key}" => $merged]);
    }
}
