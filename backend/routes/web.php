<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Finance CRM Backend API',
        'status' => 'running',
        'timestamp' => now()
    ]);
});

Route::get('/health', function () {
    return response()->json(['status' => 'healthy']);
});
