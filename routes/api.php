<?php

use App\Http\Controllers\API\ApiAccess\ApiAccessController;
use App\Http\Controllers\API\ApiAccess\HqApiAccessController;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Illuminate\Support\Facades\Route;
use Hanafalah\LaravelSupport\Facades\LaravelSupport;


$hq_domains = [
    'hq.test'
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
    'lite.test',
    env('WELLMED_CORE_DEV_URL','wellmed-core.kalpahealth.com')
];

foreach ($wellmed_lite_domains as $wellmed_lite_domain) {
    Route::domain($wellmed_lite_domain)->group(function () {
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
