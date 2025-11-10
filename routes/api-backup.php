<?php

use App\Http\Controllers\API\ApiAccess\ApiAccessController;
use App\Http\Controllers\API\ApiAccess\HqApiAccessController;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Illuminate\Support\Facades\Route;
use Hanafalah\LaravelSupport\Facades\LaravelSupport;
use Projects\WellmedLite\Controllers\API\SatuSehat\{
    Autolist\AutolistController,
    AuthController
};
use Projects\WellmedLite\Controllers\API\SatuSehat\{
    Organization\OrganizationController,
    Patient\PatientController
};

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

