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

// $hq_domains = [
//     'hq.test',
//     '10.100.26.83'
// ];

// foreach ($hq_domains as $hq_domain) {
//     Route::domain($hq_domain)->group(function () {
//         ApiAccess::secure(function(){
//             Route::apiResource('token',HqApiAccessController::class)
//                 ->only('store','destroy')
//                 ->parameters(['token' => 'uuid']);
                
//             Route::group([
//                 'prefix' => 'xendit',
//                 'as' => 'xendit.'
//             ],function(){
//                 LaravelSupport::callRoutes(__DIR__.'/xendit');
//             });
//         }); 

//     });
// }

$wellmed_lite_domains = [
    'lite.test',
    '10.100.14.65',
    env('WELLMED_CORE_DEV_URL','api-dev-wellmed.local')
];

foreach ($wellmed_lite_domains as $wellmed_lite_domain) {
    Route::domain($wellmed_lite_domain)->group(function () {
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
    });
}

Route::get('/{tenant}/assets/{path?}', 'App\Http\Controllers\TenantAssetsController@asset')
    ->where('path', '(.*)')
    ->name('stancl.tenancy.asset');

Route::group([
    'as' => 'api.'
],function(){
    LaravelSupport::callRoutes(__DIR__.'/api');
});

