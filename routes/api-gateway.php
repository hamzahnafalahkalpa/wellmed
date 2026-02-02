<?php

use App\Http\Controllers\API\ApiAccess\ApiAccessController;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Illuminate\Support\Facades\Route;
use Hanafalah\LaravelSupport\Facades\LaravelSupport;

ApiAccess::secure(function(){
    Route::apiResource('token',ApiAccessController::class)
        ->only('store','destroy')
        ->parameters(['token' => 'uuid']);
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

// Patient EMR - Visit Registration Export Routes
use Projects\WellmedGateway\Controllers\API\PatientEmr\VisitRegistration\VisitRegistrationController;

Route::prefix('patient-emr')->name('patient-emr.')->group(function () {
    // Note: Standard CRUD routes for visit-registration already exist elsewhere
    // These are additional export-specific routes

    Route::prefix('visit-registration')->name('visit-registration.')->group(function () {
        // EMR Export endpoints
        Route::post('/{id}/export', [VisitRegistrationController::class, 'export'])->name('export');
    });

    // Export status and download routes (exportId is UUID, not visit registration ID)
    Route::get('/exports/{exportId}/status', [VisitRegistrationController::class, 'exportStatus'])->name('exports.status');
    Route::get('/exports/{exportId}/download', [VisitRegistrationController::class, 'exportDownload'])->name('exports.download');
});

