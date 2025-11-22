<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JournalDownloadController;
use App\Http\Controllers\AttendanceDownloadController;
use App\Http\Controllers\RecapJournalController;

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
});

Route::get('/download-journal', [JournalDownloadController::class, 'downloadJournal'])
    ->name('download-journal')
    ->middleware('auth');

Route::get('/download-attendance', [AttendanceDownloadController::class, 'downloadAttendance'])
    ->name('download-attendance')
    ->middleware('auth');

Route::get('/recap-journal', \App\Livewire\RecapJournal::class)
    ->name('recap-journal.index')
    ->middleware('auth');

Route::get('/recap-journal/show', [RecapJournalController::class, 'show'])
    ->name('recap-journal.show')
    ->middleware('auth');

Route::get('/download-recap-journal', [RecapJournalController::class, 'downloadRecap'])
    ->name('download-recap-journal')
    ->middleware('auth');