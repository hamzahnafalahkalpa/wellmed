<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/{tenant}/assets/{path?}', 'App\Http\Controllers\TenantAssetsController@asset')
    ->where('path', '(.*)')
    ->name('stancl.tenancy.asset');
