<?php

use App\Http\Controllers\API\ApiAccess\ApiAccessController;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Illuminate\Support\Facades\Route;
use Hanafalah\LaravelSupport\Facades\LaravelSupport;


$hq_domains = [
    'localhost:8007'
];

foreach ($hq_domains as $hq_domain) {
    Route::domain($hq_domain)->group(function () {
        ApiAccess::secure(function(){
            Route::apiResource('token',HqApiAccessController::class)
                ->only('store','destroy')
                ->parameters(['token' => 'uuid']);
        }); 
    });
}

$wellmed_lite_domains = [
    'localhost:8005',
    env('WELLMED_CORE_DEV_URL','wellmed-core.kalpahealth.com')
];

foreach ($wellmed_lite_domains as $wellmed_lite_domain) {
    Route::domain($hq_domain)->group(function () {
        ApiAccess::secure(function(){
            Route::apiResource('token',ApiAccessController::class)
                ->only('store','destroy')
                ->parameters(['token' => 'uuid']);
        }); 
    });
}

Route::group([
    'as' => 'api.'
],function(){
    LaravelSupport::callRoutes(__DIR__.'/api');
});
