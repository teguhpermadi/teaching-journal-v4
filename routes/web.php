<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JournalDownloadController;

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
});

Route::get('/download-journal', [JournalDownloadController::class, 'downloadJournal'])
    ->name('download-journal')
    ->middleware('auth');