<?php

declare(strict_types=1);

namespace Gingerminds\LaravelCms\Providers;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LaravelCmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(LaravelCmsAuthServiceProvider::class);

        //        $this->app->bind(
        //            MediaController::class,
        //            ResourceResolver::controller('media')
        //        );
        //        $this->app->bind(
        //            MediaRepository::class,
        //            ResourceResolver::repository('media')
        //        );
        //        $this->app->bind(
        //            Media::class,
        //            ResourceResolver::model('media')
        //        );
        //        $this->app->bind(
        //            MediaProvider::class,
        //            ResourceResolver::provider('media')
        //        );
        //        $this->app->bind(
        //            MediaRequest::class,
        //            ResourceResolver::request('media')
        //        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/gingerminds-cms.php',
            'gingerminds-cms'
        );

        $this->tagClassesFromPath(
            __DIR__ . '/../ApiProvider',
            'Gingerminds\\LaravelCms\\ApiProvider\\',
            ProviderInterface::class
        );

        // Processors
        $this->tagClassesFromPath(
            __DIR__ . '/../StateProcessor',
            'Gingerminds\\LaravelCms\\StateProcessor\\',
            ProcessorInterface::class
        );
    }

    public function boot(): void
    {
        //Route::model('media', ResourceResolver::model('media'));

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

        //        if ($this->app->runningInConsole()) {
        //            $this->publishes([
        //                __DIR__ . '/../../resources/scss' => resource_path('scss/vendor/gingerminds-cms'),
        //                __DIR__ . '/../../resources/js'   => resource_path('js/vendor/gingerminds-cms'),
        //            ], 'gingerminds-assets');
        //        }
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
