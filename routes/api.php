<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ConfigurationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// UBL 2.1
Route::prefix('/ubl2.1')->group(function () {
    // Configuration
    Route::prefix('/config')->group(function () {
        Route::controller(ConfigurationController::class)->group(function () {
            Route::post('/{nit}/{dv?}', 'store');
        });
    });
});

Route::middleware('auth:api')->group(function () {
    // UBL 2.1
    Route::prefix('/ubl2.1')->group(function () {
        // Configuration
        Route::prefix('/config')->group(function () {
            Route::controller(ConfigurationController::class)->group(function () {
                Route::put('/software', 'storeSoftware');
                Route::put('/certificate', 'storeCertificate');
                Route::put('/resolution', 'storeResolution');
                Route::put('/environment', 'storeEnvironment');
                Route::get('/dowload-resolution', 'getResolutions');
            });
		});

		//Qualification
		Route::prefix('/qualification')->group(function () {
			Route::post('/', 'Api\QualificationController@store');
        });

        // Invoice
        Route::prefix('/invoice')->group(function () {
            Route::controller(InvoiceController::class)->group(function () {
                Route::post('/{testSetId}', 'testSetStore');
                Route::post('/', 'store');
            });
            
        });

        // Credit Notes
        Route::prefix('/credit-note')->group(function () {
            Route::post('/{testSetId}', 'Api\CreditNoteController@testSetStore');
            Route::post('/', 'Api\CreditNoteController@store');
        });

        // Debit Notes
        Route::prefix('/debit-note')->group(function () {
            Route::post('/{testSetId}', 'Api\DebitNoteController@testSetStore');
            Route::post('/', 'Api\DebitNoteController@store');
        });

        // Status
        Route::prefix('/status')->group(function () {
            Route::post('/zip/{trackId}', 'Api\StateController@zip');
            Route::post('/document/{trackId}', 'Api\StateController@document');
        });
    });
});
