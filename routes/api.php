<?php

use App\Http\Controllers\API\ApiAccess\ApiAccessController;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Illuminate\Support\Facades\Route;
use Hanafalah\LaravelSupport\Facades\LaravelSupport;
use Hanafalah\MicroTenant\Facades\MicroTenant;

ApiAccess::secure(function(){
    Route::apiResource('token',ApiAccessController::class)
        ->only('store','destroy')
        ->parameters(['token' => 'uuid']);
});


Route::delete('token/{uuid}',[ApiAccessController::class,'destroy'])->name('destroy');
LaravelSupport::callRoutes(__DIR__.'/api');