<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FormSubmissionController extends Controller
{
    public function submitEasypayForm(Request $request)
    {
        // 驗證輸入資料
        $validatedData = $request->validate([
            'customer_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'line_id' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:10',
            'channel' => 'nullable|string|max:20',
        ]);

        try {
            // 將資料插入資料庫
            DB::table('form_submissions')->insert([
                'customer_name' => $validatedData['customer_name'],
                'phone' => $validatedData['phone'],
                'line_id' => $validatedData['line_id'] ?? null,
                'region' => $validatedData['region'] ?? null,
                'website' => $validatedData['website'] ?? null,
                'channel' => $validatedData['channel'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['message' => '表單提交成功'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => '表單提交失敗', 'error' => $e->getMessage()], 500);
        }
    }

    public function getSubmissions()
    {
        try {
            $submissions = DB::table('form_submissions')->select('customer_name', 'phone', 'line_id')->get();
            return response()->json($submissions, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => '獲取數據失敗', 'error' => $e->getMessage()], 500);
        }
    }
} 