<?php

use App\Http\Controllers\AttendanceSheetController;
use App\Http\Controllers\CardDesignController;
use App\Http\Controllers\CardExportController;
use App\Http\Controllers\ExamInformationController;
use App\Http\Controllers\ExamRoomController;
use App\Http\Controllers\ExamScheduleController;
use App\Http\Controllers\ExamSessionController;
use App\Http\Controllers\ScheduleShareController;
use App\Http\Controllers\SeatingFloorPlanController;
use App\Http\Controllers\SeatingPdfController;
use App\Http\Controllers\SeatingPlanController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExamSessionController::class, 'index'])->name('exam-sessions.index');
Route::post('/sessions', [ExamSessionController::class, 'store'])->name('exam-sessions.store');
Route::get('/sessions/{examSession}', [ExamSessionController::class, 'show'])->name('exam-sessions.show');
Route::put('/sessions/{examSession}', [ExamSessionController::class, 'update'])->name('exam-sessions.update');
Route::delete('/sessions/{examSession}', [ExamSessionController::class, 'destroy'])->name('exam-sessions.destroy');

Route::prefix('/sessions/{examSession}/import')->name('exam-sessions.import.')->group(function () {
    Route::get('/', [StudentImportController::class, 'showWizard'])->name('wizard');
    Route::post('/upload', [StudentImportController::class, 'upload'])->name('upload');
    Route::post('/preview', [StudentImportController::class, 'preview'])->name('preview');
    Route::post('/commit', [StudentImportController::class, 'commit'])->name('commit');
    Route::post('/cancel', [StudentImportController::class, 'cancel'])->name('cancel');
});

Route::prefix('/sessions/{examSession}/students')->name('exam-sessions.students.')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('index');
    Route::put('/{student}', [StudentController::class, 'update'])->name('update');
    Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy');
    Route::post('/numbers/preview', [StudentController::class, 'previewNumbers'])->name('numbers.preview');
    Route::post('/numbers/apply', [StudentController::class, 'applyNumbers'])->name('numbers.apply');
});

Route::prefix('/sessions/{examSession}/rooms')->name('exam-sessions.rooms.')->group(function () {
    Route::get('/', [ExamRoomController::class, 'index'])->name('index');
    Route::post('/preview', [ExamRoomController::class, 'preview'])->name('preview');
    Route::post('/apply', [ExamRoomController::class, 'apply'])->name('apply');
    Route::post('/clear', [ExamRoomController::class, 'clear'])->name('clear');
});

Route::prefix('/sessions/{examSession}/schedules')->name('exam-sessions.schedules.')->group(function () {
    Route::get('/', [ExamScheduleController::class, 'index'])->name('index');
    Route::post('/', [ExamScheduleController::class, 'store'])->name('store');
    Route::post('/sample', [ExamScheduleController::class, 'seedSample'])->name('seed-sample');
    Route::delete('/{schedule}', [ExamScheduleController::class, 'destroy'])->name('destroy');
    Route::post('/clear', [ExamScheduleController::class, 'clear'])->name('clear');

    Route::get('/share', [ScheduleShareController::class, 'index'])->name('share.index');
    Route::post('/share', [ScheduleShareController::class, 'update'])->name('share.update');
    Route::post('/share/auto-assign', [ScheduleShareController::class, 'autoAssign'])->name('share.auto-assign');
    Route::post('/share/reset', [ScheduleShareController::class, 'reset'])->name('share.reset');
    Route::get('/share/pdf', [ScheduleShareController::class, 'exportPdf'])->name('share.pdf');
});

Route::prefix('/sessions/{examSession}/information')->name('exam-sessions.information.')->group(function () {
    Route::get('/', [ExamInformationController::class, 'edit'])->name('edit');
    Route::put('/', [ExamInformationController::class, 'update'])->name('update');
    Route::get('/assets/{type}', [ExamInformationController::class, 'asset'])->name('asset');
});

Route::prefix('/sessions/{examSession}/design')->name('exam-sessions.design.')->group(function () {
    Route::get('/', [CardDesignController::class, 'edit'])->name('edit');
    Route::put('/', [CardDesignController::class, 'update'])->name('update');
});

Route::prefix('/sessions/{examSession}/export')->name('exam-sessions.export.')->group(function () {
    Route::get('/', [CardExportController::class, 'index'])->name('index');
    Route::post('/single', [CardExportController::class, 'exportSingle'])->name('single');
    Route::post('/batch', [CardExportController::class, 'exportBatch'])->name('batch');
    Route::get('/files/{filename}', [CardExportController::class, 'downloadFile'])->name('download');
    Route::delete('/files/{filename}', [CardExportController::class, 'deleteFile'])->name('delete');
});

Route::prefix('/sessions/{examSession}/seating')->name('exam-sessions.seating.')->group(function () {
    Route::get('/', [SeatingPlanController::class, 'index'])->name('index');
    Route::post('/generate', [SeatingPlanController::class, 'generate'])->name('generate');
    Route::post('/{seatingPlan}/finalize', [SeatingPlanController::class, 'finalize'])->name('finalize');
    Route::patch('/qr-url', [SeatingPdfController::class, 'saveQrUrl'])->name('qr-url');
    Route::get('/{seatingPlan}/floor-plan', [SeatingFloorPlanController::class, 'show'])->name('floor-plan');
    Route::get('/{seatingPlan}/floor-plan/export-html', [SeatingFloorPlanController::class, 'exportHtml'])->name('floor-plan.export-html');
    Route::get('/{seatingPlan}/pdf', [SeatingPdfController::class, 'export'])->name('pdf');
});

Route::prefix('/sessions/{examSession}/attendance')->name('exam-sessions.attendance.')->group(function () {
    Route::get('/', [AttendanceSheetController::class, 'index'])->name('index');
    Route::get('/pdf', [AttendanceSheetController::class, 'exportPdf'])->name('pdf');
});