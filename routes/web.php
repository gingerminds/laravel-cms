<?php

declare(strict_types=1);

use Gingerminds\LaravelCms\Resolver\ResourceResolver;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'gingerminds-core.auth'])
    ->prefix(config('gingerminds-core.admin_prefix'))
    ->name('gingerminds-cms.')
    ->group(function () {
        Route::resource('menus', ResourceResolver::controller('menu'));
        Route::get(
            'menus/{menu}/items',
            [
                ResourceResolver::controller('menu_item'), 'index',
            ]
        )->name('menu_items.index');

        Route::get(
            'menus/{menu}/items/create',
            [
                ResourceResolver::controller('menu_item'), 'create',
            ]
        )->name('menu_items.create');

        Route::get(
            'menus/{menu}/items/{menuItem}/edit',
            [
                ResourceResolver::controller('menu_item'), 'edit',
            ]
        )->name('menu_items.edit');

        Route::post(
            'menus/{menu}/items',
            [
                ResourceResolver::controller('menu_item'), 'store',
            ]
        )->name('menu_items.store');

        Route::patch(
            'menus/{menu}/items/{menuItem}',
            [
                ResourceResolver::controller('menu_item'), 'update',
            ]
        )->name('menu_items.update');

        Route::delete(
            'menus/{menu}/items/{menuItem}',
            [
                ResourceResolver::controller('menu_item'), 'destroy',
            ]
        )->name('menu_items.destroy');

        Route::post(
            'menus/{menu}/items/reorder',
            [
                ResourceResolver::controller('menu_item'), 'reorder',
            ]
        )->name('menu_items.reorder');

        Route::resource('pages', ResourceResolver::controller('page'));
        // Hyphenated, matching MediaCategory's "media-categories" URI
        // convention. Route::resource() derives route *names* from the
        // resource name as-is (no automatic underscore<->hyphen
        // conversion), so every route() reference to this resource
        // throughout PageCategoryController/the page_categories views uses
        // "gingerminds-cms.page-categories.*", not "page_categories".
        Route::resource('page-categories', ResourceResolver::controller('page_category'));
    });
