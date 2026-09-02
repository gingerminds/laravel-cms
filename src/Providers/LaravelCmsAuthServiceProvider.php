<?php

namespace Gingerminds\LaravelCms\Providers;

use Gingerminds\LaravelCms\Policies\Menu\MenuItemPolicy;
use Gingerminds\LaravelCms\Policies\Menu\MenuPolicy;
use Gingerminds\LaravelCms\Policies\Page\PagePolicy;
use Gingerminds\LaravelCms\Policies\PageCategory\PageCategoryPolicy;
use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Gingerminds\LaravelCore\Resolver\ResourceResolver as CoreResourceResolver;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Defensive: pins the same 'user' morph alias as gingerminds-core so
        // model_has_roles/model_has_permissions stay consistent regardless of
        // provider boot order.
        Relation::morphMap([
            'user' => CoreResourceResolver::model('user'),
        ]);

        $this
            ->app
            ->make(Gate::class)
            ->policy(ResourceResolver::model('page'), PagePolicy::class);

        $this
            ->app
            ->make(Gate::class)
            ->policy(ResourceResolver::model('page_category'), PageCategoryPolicy::class);

        $this
            ->app
            ->make(Gate::class)
            ->policy(ResourceResolver::model('menu'), MenuPolicy::class);

        $this
            ->app
            ->make(Gate::class)
            ->policy(ResourceResolver::model('menu_item'), MenuItemPolicy::class);

        $this->registerPolicies();

        app(PermissionRegistrar::class)
            ->registerPermissions(app(Gate::class));
    }
}
