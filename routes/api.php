<?php

use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GradeController;
use App\Http\Controllers\Api\V1\JournalController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\TranscriptController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        // Resource routes
        Route::apiResource('journals', JournalController::class);
        Route::apiResource('attendances', AttendanceController::class);
        Route::apiResource('students', StudentController::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('academic-years', AcademicYearController::class);
        Route::apiResource('grades', GradeController::class);
        Route::apiResource('subjects', SubjectController::class);
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('transcripts', TranscriptController::class);

        // Specialized routes
        Route::post('journals/{journal}/sign-owner', [JournalController::class, 'signOwner']);
        Route::post('journals/{journal}/sign-headmaster', [JournalController::class, 'signHeadmaster']);
        Route::get('journals-calendar', [JournalController::class, 'calendar']);
        Route::post('attendances-bulk', [AttendanceController::class, 'bulkStore']);
        Route::get('attendances-report', [AttendanceController::class, 'report']);
    });
});
