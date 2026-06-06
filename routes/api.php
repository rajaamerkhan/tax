<?php

use App\Http\Controllers\Api\MockFbrController;
use Illuminate\Support\Facades\Route;

Route::prefix('mock/fbr')->group(function (): void {
    Route::post('/di_data/v1/di/postinvoicedata_sb', [MockFbrController::class, 'postInvoiceSandbox']);
    Route::post('/di_data/v1/di/postinvoicedata', [MockFbrController::class, 'postInvoiceProduction']);
    Route::post('/di_data/v1/di/validateinvoicedata_sb', [MockFbrController::class, 'validateInvoiceSandbox']);
    Route::post('/di_data/v1/di/validateinvoicedata', [MockFbrController::class, 'validateInvoiceProduction']);

    Route::get('/pdi/v1/provinces', [MockFbrController::class, 'provinces']);
    Route::get('/pdi/v1/doctypecode', [MockFbrController::class, 'documentTypes']);
    Route::get('/pdi/v1/itemdesccode', [MockFbrController::class, 'itemCodes']);
    Route::get('/pdi/v1/sroitemcode', [MockFbrController::class, 'sroItemCodes']);
    Route::get('/pdi/v1/transtypecode', [MockFbrController::class, 'transactionTypes']);
    Route::get('/pdi/v1/uom', [MockFbrController::class, 'uoms']);
    Route::get('/pdi/v1/SroSchedule', [MockFbrController::class, 'sroSchedules']);
    Route::get('/pdi/v2/SaleTypeToRate', [MockFbrController::class, 'rates']);
    Route::get('/pdi/v2/HS_UOM', [MockFbrController::class, 'hsUom']);
    Route::get('/pdi/v2/SROItem', [MockFbrController::class, 'sroItems']);
    Route::match(['get', 'post'], '/dist/v1/statl', [MockFbrController::class, 'statl']);
    Route::match(['get', 'post'], '/dist/v1/Get_Reg_Type', [MockFbrController::class, 'registrationType']);
});
