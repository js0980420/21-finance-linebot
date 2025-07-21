<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FormSubmissionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/easypay-form-submit', [FormSubmissionController::class, 'submitEasypayForm']);
Route::get('/submissions', [FormSubmissionController::class, 'getSubmissions']); 