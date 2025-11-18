<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JournalDownloadController;
use App\Http\Controllers\AttendanceDownloadController;

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
});

Route::get('/download-journal', [JournalDownloadController::class, 'downloadJournal'])
    ->name('download-journal')
    ->middleware('auth');

Route::get('/download-attendance', [AttendanceDownloadController::class, 'downloadAttendance'])
    ->name('download-attendance')
    ->middleware('auth');