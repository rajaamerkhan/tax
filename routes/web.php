<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\DemoInvoiceController;
use App\Http\Controllers\Admin\MockFbrConsoleController;
use App\Http\Controllers\Admin\ReferenceDataController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceVerificationController;
use App\Http\Controllers\InvoiceAutocompleteController;
use App\Http\Controllers\InvoiceImportController;
use App\Http\Controllers\InvoiceReferenceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('/invoices/verify/{fbrInvoiceId}', InvoiceVerificationController::class)
    ->name('invoices.verify');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::redirect('/invoice', '/invoices');
    Route::redirect('/invoice/create', '/invoices/create');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::middleware('role:admin')->group(function (): void {
        Route::get('/company', [CompanyProfileController::class, 'edit'])->name('company.edit');
        Route::put('/company', [CompanyProfileController::class, 'update'])->name('company.update');
        Route::post('/company/sync-references', [CompanyProfileController::class, 'syncReferences'])->name('company.sync-references');
        Route::get('/admin/mock-fbr-console', MockFbrConsoleController::class)->name('admin.mock-fbr-console');
        Route::post('/admin/mock-fbr-console/demo-invoice', DemoInvoiceController::class)->name('admin.mock-fbr-console.demo-invoice');
        Route::get('/reference-data', [ReferenceDataController::class, 'index'])->name('reference-data.index');
        Route::post('/reference-data/hs-codes/import', [ReferenceDataController::class, 'importHsCodes'])->name('reference-data.hs-codes.import');
        Route::get('/reference-data/hs-codes/template', [ReferenceDataController::class, 'downloadHsTemplate'])->name('reference-data.hs-codes.template');
    });

    Route::middleware('role:admin,accountant')->group(function (): void {
        Route::resource('customers', CustomerController::class)->except(['index', 'show']);
        Route::post('/imports/preview', [InvoiceImportController::class, 'preview'])->name('imports.preview');
        Route::post('/imports/{import}', [InvoiceImportController::class, 'store'])->name('imports.store');
        Route::resource('invoices', InvoiceController::class)->except(['index', 'show']);
        Route::post('/invoices/{invoice}/validate-fbr', [InvoiceController::class, 'validateWithFbr'])->name('invoices.validate-fbr');
        Route::post('/invoices/{invoice}/submit-fbr', [InvoiceController::class, 'submitToFbr'])->name('invoices.submit-fbr');
    });

    Route::get('/imports', [InvoiceImportController::class, 'index'])->name('imports.index');
    Route::get('/imports/template', [InvoiceImportController::class, 'sampleTemplate'])->name('imports.template');
    Route::get('/imports/{import}', [InvoiceImportController::class, 'show'])->name('imports.show');
    Route::resource('customers', CustomerController::class)->only(['index', 'show']);
    Route::get('/invoice-autocomplete/{resource}', InvoiceAutocompleteController::class)->name('invoices.autocomplete');
    Route::get('/invoice-reference-options', InvoiceReferenceController::class)->name('invoices.reference-options');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/download-pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.download-pdf');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
});
