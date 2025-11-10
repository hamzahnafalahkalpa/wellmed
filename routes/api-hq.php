<?php

use App\Http\Controllers\API\ApiAccess\HqApiAccessController;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Illuminate\Support\Facades\Route;
use Hanafalah\LaravelSupport\Facades\LaravelSupport;

ApiAccess::secure(function(){
    Route::apiResource('token',HqApiAccessController::class)
        ->only('store','destroy')
        ->parameters(['token' => 'uuid']);
}); 

Route::group([
    'as' => 'api.'
],function(){
    LaravelSupport::callRoutes(__DIR__.'/api');
});

