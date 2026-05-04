<?php

use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InfoUserController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ResetController;
use App\Http\Controllers\ScholarshipAcademicController;
use App\Http\Controllers\ScholarshipAccountSettingController;
use App\Http\Controllers\ScholarshipBillingController;
use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\ScholarshipDisbursedController;
use App\Http\Controllers\ScholarshipMonitoringController;
use App\Http\Controllers\ScholarshipReconciliationController;
use App\Http\Controllers\ScholarshipStudentController;
use App\Http\Controllers\SessionsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::group(['middleware' => 'auth'], function () {

    Route::get('/', [HomeController::class, 'home']);
	Route::get('dashboard', [ScholarshipController::class, 'dashboard'])->name('dashboard');
	Route::get('scholarship-system', [ScholarshipController::class, 'system'])->name('scholarship-system');
	Route::get('scholarship-system/checklist', [ScholarshipController::class, 'integrationChecklist'])->name('scholarship-system.checklist');
	Route::get('scholarship-system/monitoring/upload-history', [ScholarshipMonitoringController::class, 'uploadHistory'])->name('scholarship-monitoring.upload-history');
	Route::get('scholarship-system/students', [ScholarshipStudentController::class, 'index'])->name('scholarship-students.index');
	Route::get('scholarship-system/students/report', [ScholarshipStudentController::class, 'report'])->name('scholarship-students.report');
	Route::get('scholarship-system/students/create', [ScholarshipStudentController::class, 'create'])->name('scholarship-students.create');
	Route::post('scholarship-system/students', [ScholarshipStudentController::class, 'store'])->name('scholarship-students.store');
	Route::get('scholarship-system/students/import/template', [ScholarshipStudentController::class, 'downloadTemplate'])->name('scholarship-students.import.template');
	Route::get('scholarship-system/students/lookup/{id_no}', [ScholarshipStudentController::class, 'lookupByIdNo'])->name('scholarship-students.lookup');
	Route::post('scholarship-system/students/import', [ScholarshipStudentController::class, 'import'])->name('scholarship-students.import');
	Route::get('scholarship-system/students/{student}', [ScholarshipStudentController::class, 'show'])
		->where('student', '[0-9]+')
		->name('scholarship-students.show');
	Route::get('scholarship-system/students/{student}/edit', [ScholarshipStudentController::class, 'edit'])
		->where('student', '[0-9]+')
		->name('scholarship-students.edit');
	Route::put('scholarship-system/students/{student}', [ScholarshipStudentController::class, 'update'])
		->where('student', '[0-9]+')
		->name('scholarship-students.update');
	Route::post('scholarship-system/students/{student}/toggle-status', [ScholarshipStudentController::class, 'toggleStatus'])
		->where('student', '[0-9]+')
		->name('scholarship-students.toggle-status');
	Route::post('scholarship-system/students/{student}/remove', [ScholarshipStudentController::class, 'remove'])
		->where('student', '[0-9]+')
		->name('scholarship-students.remove');
	Route::get('scholarship-system/academic', [ScholarshipAcademicController::class, 'index'])->name('scholarship-academic.index');
	Route::get('scholarship-system/academic/years', [ScholarshipAcademicController::class, 'yearsIndex'])->name('scholarship-academic.years.index');
	Route::get('scholarship-system/academic/years/create', [ScholarshipAcademicController::class, 'yearsCreate'])->name('scholarship-academic.years.create');
	Route::post('scholarship-system/academic/years', [ScholarshipAcademicController::class, 'yearsStore'])->name('scholarship-academic.years.store');
	Route::get('scholarship-system/academic/years/{year}/edit', [ScholarshipAcademicController::class, 'yearsEdit'])
		->where('year', '[0-9]+')
		->name('scholarship-academic.years.edit');
	Route::put('scholarship-system/academic/years/{year}', [ScholarshipAcademicController::class, 'yearsUpdate'])
		->where('year', '[0-9]+')
		->name('scholarship-academic.years.update');
	Route::post('scholarship-system/academic/years/{year}/toggle-status', [ScholarshipAcademicController::class, 'yearsToggleStatus'])
		->where('year', '[0-9]+')
		->name('scholarship-academic.years.toggle-status');
	Route::post('scholarship-system/academic/years/{year}/remove', [ScholarshipAcademicController::class, 'yearsRemove'])
		->where('year', '[0-9]+')
		->name('scholarship-academic.years.remove');
	Route::get('scholarship-system/academic/semesters', [ScholarshipAcademicController::class, 'semestersIndex'])->name('scholarship-academic.semesters.index');
	Route::get('scholarship-system/academic/semesters/create', [ScholarshipAcademicController::class, 'semestersCreate'])->name('scholarship-academic.semesters.create');
	Route::post('scholarship-system/academic/semesters', [ScholarshipAcademicController::class, 'semestersStore'])->name('scholarship-academic.semesters.store');
	Route::get('scholarship-system/academic/semesters/{semester}/edit', [ScholarshipAcademicController::class, 'semestersEdit'])
		->where('semester', '[0-9]+')
		->name('scholarship-academic.semesters.edit');
	Route::put('scholarship-system/academic/semesters/{semester}', [ScholarshipAcademicController::class, 'semestersUpdate'])
		->where('semester', '[0-9]+')
		->name('scholarship-academic.semesters.update');
	Route::post('scholarship-system/academic/semesters/{semester}/toggle-status', [ScholarshipAcademicController::class, 'semestersToggleStatus'])
		->where('semester', '[0-9]+')
		->name('scholarship-academic.semesters.toggle-status');
	Route::post('scholarship-system/academic/semesters/{semester}/remove', [ScholarshipAcademicController::class, 'semestersRemove'])
		->where('semester', '[0-9]+')
		->name('scholarship-academic.semesters.remove');
	Route::get('scholarship-system/academic/year-levels', [ScholarshipAcademicController::class, 'yearLevelsIndex'])->name('scholarship-academic.year-levels.index');
	Route::get('scholarship-system/academic/year-levels/create', [ScholarshipAcademicController::class, 'yearLevelsCreate'])->name('scholarship-academic.year-levels.create');
	Route::post('scholarship-system/academic/year-levels', [ScholarshipAcademicController::class, 'yearLevelsStore'])->name('scholarship-academic.year-levels.store');
	Route::get('scholarship-system/academic/year-levels/{level}/edit', [ScholarshipAcademicController::class, 'yearLevelsEdit'])
		->where('level', '[0-9]+')
		->name('scholarship-academic.year-levels.edit');
	Route::put('scholarship-system/academic/year-levels/{level}', [ScholarshipAcademicController::class, 'yearLevelsUpdate'])
		->where('level', '[0-9]+')
		->name('scholarship-academic.year-levels.update');
	Route::post('scholarship-system/academic/year-levels/{level}/toggle-status', [ScholarshipAcademicController::class, 'yearLevelsToggleStatus'])
		->where('level', '[0-9]+')
		->name('scholarship-academic.year-levels.toggle-status');
	Route::post('scholarship-system/academic/year-levels/{level}/remove', [ScholarshipAcademicController::class, 'yearLevelsRemove'])
		->where('level', '[0-9]+')
		->name('scholarship-academic.year-levels.remove');
	Route::get('scholarship-system/academic/programs', [ScholarshipAcademicController::class, 'programsIndex'])->name('scholarship-academic.programs.index');
	Route::get('scholarship-system/academic/programs/create', [ScholarshipAcademicController::class, 'programsCreate'])->name('scholarship-academic.programs.create');
	Route::post('scholarship-system/academic/programs', [ScholarshipAcademicController::class, 'programsStore'])->name('scholarship-academic.programs.store');
	Route::get('scholarship-system/academic/programs/{program}/edit', [ScholarshipAcademicController::class, 'programsEdit'])
		->where('program', '[0-9]+')
		->name('scholarship-academic.programs.edit');
	Route::put('scholarship-system/academic/programs/{program}', [ScholarshipAcademicController::class, 'programsUpdate'])
		->where('program', '[0-9]+')
		->name('scholarship-academic.programs.update');
	Route::post('scholarship-system/academic/programs/{program}/toggle-status', [ScholarshipAcademicController::class, 'programsToggleStatus'])
		->where('program', '[0-9]+')
		->name('scholarship-academic.programs.toggle-status');
	Route::post('scholarship-system/academic/programs/{program}/remove', [ScholarshipAcademicController::class, 'programsRemove'])
		->where('program', '[0-9]+')
		->name('scholarship-academic.programs.remove');
	Route::get('scholarship-system/fund-report', [ScholarshipDisbursedController::class, 'fundReport'])->name('scholarship-fund-report.index');
	Route::get('scholarship-system/billing-report', [ScholarshipBillingController::class, 'index'])->name('scholarship-billing.index');
	Route::get('scholarship-system/billing-entry', [ScholarshipBillingController::class, 'create'])->name('scholarship-billing.create');
	Route::post('scholarship-system/billing-entry', [ScholarshipBillingController::class, 'store'])->name('scholarship-billing.store');
	Route::get('scholarship-system/billing-entry/template', [ScholarshipBillingController::class, 'entryTemplate'])->name('scholarship-billing.entry.template');
	Route::get('scholarship-system/billing-import', [ScholarshipBillingController::class, 'importForm'])->name('scholarship-billing.import.form');
	Route::get('scholarship-system/billing-import/template/{type?}', [ScholarshipBillingController::class, 'importTemplate'])->name('scholarship-billing.import.template');
	Route::post('scholarship-system/billing-import', [ScholarshipBillingController::class, 'importProcess'])->name('scholarship-billing.import.process');
	Route::get('scholarship-system/billing-summary', [ScholarshipBillingController::class, 'summary'])->name('scholarship-billing.summary');
	Route::get('scholarship-system/billing-report/{batch}', [ScholarshipBillingController::class, 'show'])
		->where('batch', '[0-9]+')
		->name('scholarship-billing.show');
	Route::post('scholarship-system/billing-report/{batch}/archive', [ScholarshipBillingController::class, 'archive'])
		->where('batch', '[0-9]+')
		->name('scholarship-billing.archive');
	Route::get('scholarship-system/disbursed-report', [ScholarshipDisbursedController::class, 'fundReport'])->name('scholarship-disbursed.report');
	Route::get('scholarship-system/disbursed-report/{batch}', [ScholarshipDisbursedController::class, 'show'])
		->where('batch', '[0-9]+')
		->name('scholarship-disbursed.show');
	Route::get('scholarship-system/disbursed-entry', [ScholarshipDisbursedController::class, 'entryForm'])->name('scholarship-disbursed.entry.form');
	Route::post('scholarship-system/disbursed-entry', [ScholarshipDisbursedController::class, 'entryStore'])->name('scholarship-disbursed.entry.store');
    Route::post('scholarship-system/disbursed-report/{batch}/fast-finalize', [ScholarshipDisbursedController::class, 'fastFinalizeBatch'])->name('scholarship-disbursed.fast-finalize');
	Route::get('scholarship-system/disbursed-import', [ScholarshipDisbursedController::class, 'importForm'])->name('scholarship-disbursed.import.form');
	Route::get('scholarship-system/disbursed-import/template', [ScholarshipDisbursedController::class, 'importTemplate'])->name('scholarship-disbursed.import.template');
	Route::post('scholarship-system/disbursed-import', [ScholarshipDisbursedController::class, 'importProcess'])->name('scholarship-disbursed.import.process');
	Route::get('scholarship-system/reconciliation', [ScholarshipReconciliationController::class, 'index'])->name('scholarship-reconciliation.index');
	Route::get('scholarship-system/account-setting', [ScholarshipAccountSettingController::class, 'index'])->name('scholarship-account-setting.index');
	Route::post('scholarship-system/account-setting', [ScholarshipAccountSettingController::class, 'update'])->name('scholarship-account-setting.update');
	Route::get('scholarship-system/launch/{module}', [ScholarshipController::class, 'launch'])
		->where('module', '[a-z0-9\-]+')
		->name('scholarship-system.launch');
	Route::get('scholarship-system/module/{module}', [ScholarshipController::class, 'module'])
		->where('module', '[a-z0-9\-]+')
		->name('scholarship-system.module');

	Route::get('billing', function () {
		return view('billing');
	})->name('billing');

	Route::get('profile', function () {
		return view('profile');
	})->name('profile');

	Route::get('rtl', function () {
		return view('rtl');
	})->name('rtl');

	Route::get('user-management', function () {
		return view('laravel-examples/user-management');
	})->name('user-management');

	Route::get('tables', function () {
		return view('tables');
	})->name('tables');

    Route::get('virtual-reality', function () {
		return view('virtual-reality');
	})->name('virtual-reality');

    Route::get('static-sign-in', function () {
		return view('static-sign-in');
	})->name('sign-in');

    Route::get('static-sign-up', function () {
		return view('static-sign-up');
	})->name('sign-up');

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

Route::get('scholarship-system/bridge/consume', [ScholarshipController::class, 'consumeBridgeToken'])
	->name('scholarship-system.bridge.consume');