<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->prefix(config('gingerminds-core.admin_prefix'))
    ->name('gingerminds-cms.')
    ->group(function () {
    });
