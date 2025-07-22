<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MortgageFormController;
use App\Http\Controllers\LineBotController;

Route::get('/', function () {
    return 'Laravel is working! Database: sqlite | PHP: ' . phpversion();
});

Route::get('/simple-login', function () {
    return '
    <!DOCTYPE html>
    <html>
    <head><title>Simple Login</title></head>
    <body>
        <h1>Laravel 登入測試</h1>
        <form>
            <p>選擇角色：</p>
            <button type="button" onclick="window.location.href=\'/dashboard\'">管理員登入</button>
        </form>
    </body>
    </html>';
});

Route::get('/test', function () {
    return view('welcome');
});

// Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'mortgage-linebot',
        'timestamp' => now()->toISOString()
    ]);
});

// 房貸表單 API
Route::post('/api/mortgage-form-webhook', [MortgageFormController::class, 'handleWebhook']);
Route::post('/api/mortgage-form', [MortgageFormController::class, 'submitForm']);
Route::get('/api/stats', [MortgageFormController::class, 'getStats']);

// LINE Bot Webhook
Route::post('/webhook', [LineBotController::class, 'webhook']);

// 簡化的登入路由
Route::get('/login', function () {
    return '
    <!DOCTYPE html>
    <html lang="zh-TW">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>貸款案件管理系統 CRM 登入</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 500px; margin: 100px auto; padding: 20px; background: #f5f5f5; }
            .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            h1 { text-align: center; color: #333; margin-bottom: 30px; }
            .role-btn { display: block; width: 100%; padding: 15px; margin: 10px 0; background: #007bff; color: white; text-decoration: none; text-align: center; border-radius: 5px; border: none; font-size: 16px; cursor: pointer; }
            .role-btn:hover { background: #0056b3; }
            .admin { background: #dc3545; } .admin:hover { background: #c82333; }
            .manager { background: #28a745; } .manager:hover { background: #218838; }
            .sales { background: #ffc107; color: #000; } .sales:hover { background: #e0a800; }
            .dealer { background: #6c757d; } .dealer:hover { background: #5a6268; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>🏢 貸款案件管理系統</h1>
            <p style="text-align: center; color: #666; margin-bottom: 30px;">請選擇您的角色登入</p>
            
            <a href="/dashboard?role=admin" class="role-btn admin">
                👨‍💼 系統管理員<br><small>查看所有資料和系統設置</small>
            </a>
            
            <a href="/dashboard?role=manager" class="role-btn manager">
                👥 區域經理<br><small>管理區域業務和團隊</small>
            </a>
            
            <a href="/dashboard?role=sales" class="role-btn sales">
                💼 業務專員<br><small>管理個人客戶資料</small>
            </a>
            
            <a href="/dashboard?role=dealer" class="role-btn dealer">
                🏪 經銷商<br><small>查看相關業務資料</small>
            </a>
            
            <div style="margin-top: 30px; text-align: center; color: #999; font-size: 12px;">
                Laravel ' . app()->version() . ' | PHP ' . phpversion() . '
            </div>
        </div>
    </body>
    </html>';
})->name('login');

// 儀表板路由
Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/submissions', [App\Http\Controllers\DashboardController::class, 'submissions'])->name('dashboard.submissions');
Route::get('/dashboard/sales-accounts', [App\Http\Controllers\DashboardController::class, 'salesAccounts'])->name('dashboard.sales-accounts');
Route::get('/dashboard/query-logs', [App\Http\Controllers\DashboardController::class, 'queryLogs'])->name('dashboard.query-logs');
Route::get('/dashboard/statistics', [App\Http\Controllers\DashboardController::class, 'statistics'])->name('dashboard.statistics');

// 靜態頁面
Route::get('/admin', function () {
    return view('admin');
});

Route::get('/demo', function () {
    return view('demo');
});

Route::get('/mortgage-form', function () {
    return view('mortgage-form');
});
