<?php

use App\Http\Controllers\API\ApiAccess\ApiAccessController;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Illuminate\Support\Facades\Route;
use Hanafalah\LaravelSupport\Facades\LaravelSupport;

ApiAccess::secure(function(){
    Route::apiResource('token',ApiAccessController::class)
        ->only('store','destroy')
        ->parameters(['token' => 'uuid']);

    Route::group([
        'prefix' => 'satu-sehat',
        'as' => 'satu-sehat.'
    ],function(){
        LaravelSupport::callRoutes(__DIR__.'/satu-sehat');
    });
}); 

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'env' => config('app.env'),
        'version' => '1.0.0',
        'time' => now()->toDateTimeString(),
    ]);
});

