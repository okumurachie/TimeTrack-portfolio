<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\User\AttendanceController as UserAttendanceController;
use App\Http\Controllers\CorrectionController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])
    ->middleware(['guest:admin'])
    ->name('admin.login');

Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store'])
    ->middleware(['guest:admin']);

Route::prefix('admin')->middleware(['auth:admin'])->group(function () {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendances.index');
    Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'detail'])->name('admin.detail.record');
    Route::patch('/attendance/{attendance}', [AdminAttendanceController::class, 'adminUpdate'])->name('admin.attendance.update');
    Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])->name('admin.staff.list');
    Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'showStaffRecord'])->name('staff-record.list');
    Route::get('/stamp_correction_request/list', [CorrectionController::class, 'index'])->name('admin.correction.list');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [CorrectionController::class, 'show'])->name('correction.approval.show');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [CorrectionController::class, 'approve'])->name('correction.approval');
    Route::post('/export/{id}', [AdminAttendanceController::class, 'export'])->name('admin.export');
    Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
        ->name('admin.logout');
});



Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('attendance.index');
})->middleware(['auth:web', 'signed'])->name('verification.verify');

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/attendance', [UserAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/stamp', [UserAttendanceController::class, 'stamp'])->name('attendance.stamp');
    Route::get('/attendance/list', [UserAttendanceController::class, 'showMyRecord'])->name('my-record.list');
    Route::get('/attendance/detail/{id}', [UserAttendanceController::class, 'detail'])->name('user.detail.record');
    Route::post('/attendance/detail/{id}', [UserAttendanceController::class, 'store'])->name('attendance.request');
    Route::get('/stamp_correction_request/list', [CorrectionController::class, 'index'])->name('user.correction.list');
});
