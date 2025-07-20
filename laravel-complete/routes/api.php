<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API endpoints for case management system (dashboard.html)
Route::get('/applications', [ApplicationController::class, 'getApplications']);
Route::get('/sales-staff', [ApplicationController::class, 'getSalesStaff']);
Route::post('/applications/{id}/update-field', [ApplicationController::class, 'updateApplicationField']);