<?php

namespace App\Http\Controllers;

use App\Models\MortgageFormSubmission;
use App\Models\SalesLineAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApplicationController extends Controller
{
    /**
     * 獲取所有案件數據供案件管理系統使用
     */
    public function getApplications()
    {
        try {
            // 從 MortgageFormSubmission 獲取實際的表單提交數據
            $submissions = MortgageFormSubmission::orderBy('submitted_at', 'desc')->get();
            
            // 轉換為案件管理系統需要的格式
            $applications = $submissions->map(function ($submission, $index) {
                return [
                    'id' => $submission->id,
                    'case_number' => 'CASE' . str_pad($submission->id, 6, '0', STR_PAD_LEFT),
                    'application_date' => $submission->submitted_at ? $submission->submitted_at->toISOString() : now()->toISOString(),
                    'customer_name' => $submission->customer_name,
                    'phone' => $submission->phone,
                    'region' => $submission->region,
                    'line_id' => $submission->line_id,
                    
                    // 案件管理系統的新欄位 - 預設值或從現有資料推導
                    'sales_staff_name' => $submission->assigned_to ?: '',
                    'website' => $this->determineWebsiteFromSource($submission),
                    'channel' => $this->determineChannelFromSource($submission),
                    'lead_status' => '',
                    'follow_status' => '',
                    'notes' => '',
                    'submission_status' => '',
                    'case_status' => '',
                    'approved_amount' => '',
                    'disbursed_amount' => '',
                    'disbursement_status' => '',
                    
                    // 保留原有欄位作為相容性
                    'applicant_name' => $submission->customer_name,
                    'applicant_line_name' => $submission->line_id ?: $submission->customer_name,
                    'email' => '',
                    'source_website' => $this->determineWebsiteFromSource($submission),
                    'sales_staff_name' => $submission->assigned_to ?: '',
                    'loan_type' => '',
                    'collateral_item' => '',
                    'monthly_payment' => '',
                    'installment_periods' => '',
                    'status' => 'pending',
                    'can_submit' => false,
                    'has_negotiated' => false
                ];
            });

            return response()->json($applications);

        } catch (\Exception $e) {
            Log::error('獲取案件數據錯誤', ['error' => $e->getMessage()]);
            return response()->json([], 500);
        }
    }

    /**
     * 獲取業務人員列表
     */
    public function getSalesStaff()
    {
        try {
            $salesStaff = SalesLineAccount::active()->get()->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'name' => $staff->sales_name,
                    'department' => $staff->department ?: '業務部',
                    'sales_code' => $staff->sales_code,
                    'line_user_id' => $staff->line_user_id
                ];
            });

            return response()->json($salesStaff);

        } catch (\Exception $e) {
            Log::error('獲取業務人員錯誤', ['error' => $e->getMessage()]);
            return response()->json([], 500);
        }
    }

    /**
     * 更新案件欄位
     */
    public function updateApplicationField(Request $request, $id)
    {
        try {
            $field = $request->input('field');
            $value = $request->input('value');

            // 這裡可以根據需要更新數據庫
            // 暫時返回成功，實際使用時可以添加數據庫更新邏輯
            
            Log::info("更新案件 {$id} 的 {$field} 為 {$value}");

            return response()->json([
                'success' => true,
                'message' => '更新成功'
            ]);

        } catch (\Exception $e) {
            Log::error('更新案件欄位錯誤', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => '更新失敗'
            ], 500);
        }
    }

    /**
     * 根據來源判斷網站
     */
    private function determineWebsiteFromSource($submission)
    {
        $sourceUrl = $submission->source_url ?: '';
        $utmSource = $submission->utm_source ?: '';
        
        // 根據 URL 或 UTM 來源判斷網站代號
        if (strpos($sourceUrl, 'easypay-life.com.tw') !== false) {
            return 'G01'; // 易付生活網站
        }
        
        // 可以根據不同的來源設定不同的網站代號
        switch ($utmSource) {
            case 'facebook': return 'G02';
            case 'google': return 'G03';
            case 'line': return 'G04';
            default: return 'G01';
        }
    }

    /**
     * 根據來源判斷管道
     */
    private function determineChannelFromSource($submission)
    {
        $sourceUrl = $submission->source_url ?: '';
        
        if (strpos($sourceUrl, 'contact') !== false) {
            return '表單';
        }
        
        if ($submission->line_id) {
            return 'Line@';
        }
        
        return '表單'; // 預設為表單
    }
}