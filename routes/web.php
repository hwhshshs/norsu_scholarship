<?php

use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\InfoUserController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ResetController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\StudentInfoController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DisbursementController;
use App\Http\Controllers\FundReportController;
use App\Http\Controllers\SessionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => 'auth'], function () {

    Route::get('/', fn() => redirect('dashboard'));
    Route::get('dashboard', [ScholarshipController::class, 'dashboard'])->name('dashboard');

    // Student Info
    Route::get('student-info', [StudentInfoController::class, 'index'])->name('student-info.index');
    Route::get('student-info/create', [StudentInfoController::class, 'create'])->name('student-info.create');
    Route::post('student-info', [StudentInfoController::class, 'store'])->name('student-info.store');
    Route::post('student-info/import', [StudentInfoController::class, 'import'])->name('student-info.import');
    Route::get('student-info/template', [StudentInfoController::class, 'downloadTemplate'])->name('student-info.template');
    Route::get('student-info/{student}', [StudentInfoController::class, 'show'])->name('student-info.show');
    Route::get('student-info/{student}/edit', [StudentInfoController::class, 'edit'])->name('student-info.edit');
    Route::put('student-info/{student}', [StudentInfoController::class, 'update'])->name('student-info.update');
    Route::post('student-info/{student}/quick-update', [StudentInfoController::class, 'quickUpdate'])->name('student-info.quick-update');
    Route::get('student-info/{student}/history', [StudentInfoController::class, 'getHistory'])->name('student-info.history');

    // Billing
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('billing/create', [BillingController::class, 'create'])->name('billing.create');
    Route::post('billing', [BillingController::class, 'store'])->name('billing.store');
    Route::post('billing/bulk-import', [BillingController::class, 'bulkImport'])->name('billing.bulk-import');
    Route::post('billing/{batch}/import-scholars', [BillingController::class, 'importScholars'])->name('billing.import-scholars');
    Route::get('billing/template', [BillingController::class, 'downloadTemplate'])->name('billing.template');
    Route::get('billing/quick-template', [BillingController::class, 'downloadQuickTemplate'])->name('billing.quick-template');
    Route::get('billing/{batch}', [BillingController::class, 'show'])->name('billing.show');
    Route::get('billing/{batch}/edit', [BillingController::class, 'edit'])->name('billing.edit');
    Route::put('billing/{batch}', [BillingController::class, 'update'])->name('billing.update');
    Route::post('billing/{batch}/remove-attachment', [BillingController::class, 'removeAttachment'])->name('billing.remove-attachment');
    Route::post('billing/{batch}/resolve-conflict', [BillingController::class, 'resolveConflict'])->name('billing.resolve-conflict');

    // Disbursement
    Route::get('disbursement', [DisbursementController::class, 'index'])->name('disbursement.index');
    Route::get('disbursement/{batch}', [DisbursementController::class, 'show'])->name('disbursement.show');
    Route::get('disbursement/{batch}/export-csv', [DisbursementController::class, 'exportCsv'])->name('disbursement.export-csv');
    Route::get('disbursement/{batch}/print-report', [DisbursementController::class, 'printReport'])->name('disbursement.print-report');
    Route::get('disbursement-export-all', [DisbursementController::class, 'exportAllCsv'])->name('disbursement.export-all-csv');
    Route::get('disbursement-master-summary', [DisbursementController::class, 'masterSummaryPdf'])->name('disbursement.master-summary');

    // Fund Report
    Route::get('fund-report', [FundReportController::class, 'index'])->name('fund-report.index');

    // Admin
    Route::group(['middleware' => 'admin', 'prefix' => 'admin', 'as' => 'admin.'], function () {
        Route::get('activity-logs', [AdminController::class, 'activityLogs'])->name('activity-logs');
        Route::get('staff', [AdminController::class, 'staffIndex'])->name('staff.index');
        Route::post('staff', [AdminController::class, 'staffStore'])->name('staff.store');
        Route::put('staff/{user}', [AdminController::class, 'staffUpdate'])->name('staff.update');
        Route::delete('staff/{user}', [AdminController::class, 'staffDelete'])->name('staff.delete');
    });

    Route::get('/logout', [SessionsController::class, 'destroy']);
    Route::get('/user-profile', [InfoUserController::class, 'create']);
    Route::post('/user-profile', [InfoUserController::class, 'store']);
});

Route::group(['middleware' => 'guest'], function () {
    Route::get('/register', [RegisterController::class, 'create']);
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [SessionsController::class, 'create'])->name('login');
    Route::post('/session', [SessionsController::class, 'store']);
    Route::get('/login/forgot-password', [ResetController::class, 'create']);
    Route::post('/forgot-password', [ResetController::class, 'sendEmail']);
    Route::get('/reset-password/{token}', [ResetController::class, 'resetPass'])->name('password.reset');
    Route::post('/reset-password', [ChangePasswordController::class, 'changePassword'])->name('password.update');
});