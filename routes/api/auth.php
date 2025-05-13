<?php

use App\Http\Controllers\API\ApiAccess\ApiAccessController;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Illuminate\Support\Facades\Route;

ApiAccess::secure(function(){
    Route::apiResource('token',ApiAccessController::class)
        ->only('store','destroy')
        ->parameters(['token' => 'uuid']);
});