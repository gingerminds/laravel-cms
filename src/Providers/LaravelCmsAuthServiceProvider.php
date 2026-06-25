<?php

namespace Gingerminds\LaravelCms\Providers;

//use Gingerminds\LaravelMediaManager\Policies\Media\MediaCategoryPolicy;
//use Gingerminds\LaravelMediaManager\Resolver\ResourceResolver;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class LaravelCmsAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //$this->app->make(Gate::class)->policy(ResourceResolver::model('media_category'), MediaCategoryPolicy::class);

        $this->registerPolicies();

        app(PermissionRegistrar::class)
            ->registerPermissions(app(Gate::class));
    }
}
