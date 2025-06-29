<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::controller(App\Http\Controllers\RemittanceController::class)->group(function () {
    Route::post('/stores', 'getStores');
//    Route::post('/read/info', 'readOnly');//???
    Route::get('/readOnly1', 'readOnly1');
    Route::get('/readOnly', 'readOnly');
});

Route::controller(App\Http\Controllers\InvoiceBarcodeController::class)->group(function () {
    Route::prefix('barcode')->group(function () {
        Route::post('/', 'index');
        Route::post('/show/{invoiceBarcode}', 'show');
        Route::post('/store', 'store');
        Route::post('/update/{invoiceBarcode}', 'update');
        Route::post('/destroy/{invoiceBarcode}', 'destroy');
    });
    Route::post('/safe/delete/barcode', 'safeDeleteBarcodes');

});


Route::controller(App\Http\Controllers\CacheController::class)->group(function () {
    Route::post('/cache', 'cacheInvoice');
});

Route::controller(App\Http\Controllers\InvoiceController::class)->group(function () {
    Route::get('/info', 'info');
    Route::post('/update', 'updateInvoiceItems');
    Route::post('/erp', 'showInventoryVoucher');

    Route::post('/product/{id}', 'showProduct');
    Route::post('/productTest/{id}', 'showProductTest');//??
});

Route::controller(App\Http\Controllers\ReportController::class)->group(function () {
    Route::get('/report', 'report');
    Route::post('/fix', 'fix');
    Route::get('/filter', 'filter');
    Route::get('/test', 'test');

});

