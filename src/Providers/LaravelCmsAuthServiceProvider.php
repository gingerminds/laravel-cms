<?php

namespace Gingerminds\LaravelCms\Providers;

use Gingerminds\LaravelCms\Policies\Page\PagePolicy;
use Gingerminds\LaravelCms\Policies\PageCategory\PageCategoryPolicy;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
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
        $this
            ->app
            ->make(Gate::class)
            ->policy(ResourceResolver::model('page'), PagePolicy::class);

        $this
            ->app
            ->make(Gate::class)
            ->policy(ResourceResolver::model('page_category'), PageCategoryPolicy::class);

        $this->registerPolicies();

        app(PermissionRegistrar::class)
            ->registerPermissions(app(Gate::class));
    }
}
