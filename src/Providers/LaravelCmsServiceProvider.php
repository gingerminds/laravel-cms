<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Providers;

use ApiPlatform\State\ProviderInterface;
use Gingerminds\LaravelCms\ApiProvider\Menu\MenuProvider;
use Gingerminds\LaravelCms\ApiProvider\Page\PageProvider;
use Gingerminds\LaravelCms\ApiProvider\Search\SearchProvider;
use Gingerminds\LaravelCms\ApiProvider\Search\SearchResultProvider;
use Gingerminds\LaravelCms\Console\Commands\Make\CreateBlock;
use Gingerminds\LaravelCms\Console\Commands\Search\ReindexSearchCommand;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuController;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuItemController;
use Gingerminds\LaravelCms\Http\Controllers\Page\PageBlockController;
use Gingerminds\LaravelCms\Http\Controllers\Page\PageController;
use Gingerminds\LaravelCms\Http\Controllers\PageCategory\PageCategoryController;
use Gingerminds\LaravelCms\Http\Middleware\Api\InjectPageFiltersMiddleware;
use Gingerminds\LaravelCms\Http\Middleware\Api\InjectSearchFiltersMiddleware;
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
use Gingerminds\LaravelCms\Models\Search\SearchIndex;
use Gingerminds\LaravelCms\Observers\Search\SearchExclusionCascadeObserver;
use Gingerminds\LaravelCms\Observers\Search\SearchIndexObserver;
use Gingerminds\LaravelCms\Repositories\Filters\Handlers\SearchFacetFilterHandler;
use Gingerminds\LaravelCms\Repositories\Menu\MenuItemRepository;
use Gingerminds\LaravelCms\Repositories\Menu\MenuRepository;
use Gingerminds\LaravelCms\Repositories\Page\PageRepository;
use Gingerminds\LaravelCms\Repositories\PageCategory\PageCategoryRepository;
use Gingerminds\LaravelCms\Repositories\Search\SearchRepository;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCms\Services\Page\PageFilterStore;
use Gingerminds\LaravelCms\Services\Search\SearchFilterStore;
use Gingerminds\LaravelCore\Repositories\Filters\FilterHandlerRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LaravelCmsServiceProvider extends ServiceProvider
{
    private const string CONFIG_PATH = __DIR__ . '/../../config/gingerminds-cms.php';

    /** @var array<string, mixed>|null */
    private ?array $packageConfigDefaults = null;

    public function register(): void
    {
        $this->app->register(LaravelCmsAuthServiceProvider::class);

        $this->mergeConfigFrom(self::CONFIG_PATH, 'gingerminds-cms');

        $this->mergeAdditiveArrayConfig('disabled_blocks');
        $this->mergeAdditiveArrayConfig('block_paths', 'path');
        $this->mergeAdditiveArrayConfig('reference_resolvers', associative: true);
        $this->mergeAdditiveArrayConfig('search_resources', associative: true);

        $this->bindResources();

        // Shared across a single request between an ApiProvider (which
        // computes the filters) and the Inject*FiltersMiddleware that reads
        // them back to merge into the response — without singleton() each
        // side gets its own instance and the middleware only ever sees an
        // empty store.
        $this->app->singleton(PageFilterStore::class);
        $this->app->singleton(SearchFilterStore::class);

        // extend(), not a direct app(FilterHandlerRegistry::class)->register()
        // call: the registry is bound lazily by LaravelCoreServiceProvider (a
        // closure pre-registering the built-in handlers), and this provider's
        // register() can run before or after that one — extend() defers until
        // whichever binding resolves it first, so the built-ins are always in
        // place before 'facet' is added on top.
        $this->app->extend(FilterHandlerRegistry::class, function (FilterHandlerRegistry $registry) {
            $registry->register('facet', new SearchFacetFilterHandler());

            return $registry;
        });

        $this->tagClassesFromPath(
            __DIR__ . '/../ApiProvider',
            'Gingerminds\\LaravelCms\\ApiProvider\\',
            ProviderInterface::class
        );

        config([
            'api-platform.routes.middleware' => array_values(array_unique(array_merge(
                config('api-platform.routes.middleware', []),
                [InjectPageFiltersMiddleware::class, InjectSearchFiltersMiddleware::class]
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

        ResourceResolver::model('page')::observe(SearchIndexObserver::class);
        ResourceResolver::model('page_category')::observe(SearchExclusionCascadeObserver::class);

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
            self::CONFIG_PATH => config_path('gingerminds-cms.php'),
        ], 'gingerminds-cms-config');

        // Publication des assets JS/SCSS, même tag et même convention que
        // laravel-core et laravel-media-manager : chaque package publie ses
        // resources/js et resources/scss sous js|scss/vendor/<package>, le
        // projet consommateur important lui-même vendor/gingerminds-cms/app
        // dans son propre resources/scss/app.scss.
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../resources/scss' => resource_path('scss/vendor/gingerminds-cms'),
                __DIR__ . '/../../resources/js' => resource_path('js/vendor/gingerminds-cms'),
            ], 'gingerminds-assets');

            // Lets a project customize the block/preview stubs used by
            // `make:cms-block` the same way `gingerminds-core` publishes
            // its own (checked first by CreateBlock::stub()).
            $this->publishes([
                __DIR__ . '/../../stubs' => base_path('stubs/vendor/gingerminds-cms'),
            ], 'gingerminds-stubs');

            $this->commands([
                CreateBlock::class,
                ReindexSearchCommand::class,
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

        $this->app->bind(
            SearchRepository::class,
            ResourceResolver::repository('search')
        );
        $this->app->bind(
            SearchIndex::class,
            ResourceResolver::model('search')
        );
        $this->app->bind(
            SearchProvider::class,
            ResourceResolver::provider('search')
        );
        $this->app->bind(
            SearchResultProvider::class,
            ResourceResolver::provider('search_result')
        );
    }

    private function mergeAdditiveArrayConfig(string $key, ?string $uniqueBy = null, bool $associative = false): void
    {
        $default   = $this->packageConfigDefaults()[$key] ?? [];
        $published = config("gingerminds-cms.{$key}", []);

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

    /**
     * @return array<string, mixed>
     */
    private function packageConfigDefaults(): array
    {
        return $this->packageConfigDefaults ??= require self::CONFIG_PATH;
    }
}
