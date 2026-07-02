<?php

declare(strict_types=1);

use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuController;
use Gingerminds\LaravelCms\Http\Controllers\Menu\MenuItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'gingerminds-core.auth'])
    ->prefix(config('gingerminds-core.admin_prefix'))
    ->name('gingerminds-cms.')
    ->group(function () {
        Route::resource('menus', MenuController::class);
        Route::get(
            'menus/{menu}/items',
            [
                MenuItemController::class, 'index',
            ]
        )->name('menu_items.index');

        Route::get(
            'menus/{menu}/items/create',
            [
                MenuItemController::class, 'create',
            ]
        )->name('menu_items.create');

        Route::get(
            'menus/{menu}/items/{menuItem}/edit',
            [
                MenuItemController::class, 'edit',
            ]
        )->name('menu_items.edit');

        Route::post(
            'menus/{menu}/items',
            [
                MenuItemController::class, 'store',
            ]
        )->name('menu_items.store');

        Route::patch(
            'menus/{menu}/items/{menuItem}',
            [
                MenuItemController::class, 'update',
            ]
        )->name('menu_items.update');

        Route::delete(
            'menus/{menu}/items/{menuItem}',
            [
                MenuItemController::class, 'destroy',
            ]
        )->name('menu_items.destroy');

        Route::post(
            'menus/{menu}/items/reorder',
            [
                MenuItemController::class, 'reorder',
            ]
        )->name('menu_items.reorder');
    });
