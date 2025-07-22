<?php

namespace App\Http\Controllers;

use App\Models\MortgageFormSubmission;
use App\Models\SalesLineAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MortgageFormController extends Controller
{
    /**
     * 接收房貸表單提交 (Webhook)
     */
    public function handleWebhook(Request $request)
    {
        try {
            Log::info('收到房貸表單提交', $request->all());
            
            $validator = Validator::make($request->all(), [
                'customer_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'region' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '缺少必填欄位：姓名、手機號碼、區域',
                    'errors' => $validator->errors()
                ], 400);
            }

            $submission = MortgageFormSubmission::create([
                'customer_name' => $request->customer_name,
                'phone' => $request->phone,
                'line_id' => $request->line_id,
                'region' => $request->region,
                'source_url' => $request->source_url,
                'utm_source' => $request->utm_source,
                'utm_medium' => $request->utm_medium,
                'utm_campaign' => $request->utm_campaign,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer_url' => $request->header('referer'),
                'submitted_at' => now()
            ]);

            Log::info("新表單提交成功，ID: {$submission->id}");

            // 通知相關業務員
            $this->notifySalesTeam($submission->id);

            return response()->json([
                'success' => true,
                'message' => '表單提交成功',
                'submission_id' => $submission->id
            ]);

        } catch (\Exception $e) {
            Log::error('處理表單提交錯誤', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => '內部伺服器錯誤'
            ], 500);
        }
    }

    /**
     * 手動接收表單資料 (AJAX提交)
     */
    public function submitForm(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|regex:/^09\d{8}$/',
                'area' => 'required|string|max:255',
            ], [
                'phone.regex' => '請輸入正確的手機號碼格式'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '請填寫完整資料：姓名、手機號碼、區域',
                    'errors' => $validator->errors()
                ], 400);
            }

            $submission = MortgageFormSubmission::create([
                'customer_name' => $request->name,
                'phone' => $request->phone,
                'line_id' => $request->lineId,
                'region' => $request->area,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'submitted_at' => now()
            ]);

            // 通知業務團隊
            $this->notifySalesTeam($submission->id);

            return response()->json([
                'success' => true,
                'message' => '資料提交成功，我們會盡快與您聯繫',
                'submission_id' => $submission->id
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) { // 重複鍵錯誤
                return response()->json([
                    'success' => false,
                    'message' => '您今天已經提交過表單，我們會盡快與您聯繫'
                ], 409);
            }

            Log::error('表單提交錯誤', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => '系統暫時忙碌，請稍後再試'
            ], 500);
        }
    }

    /**
     * 獲取表單統計
     */
    public function getStats()
    {
        try {
            $total = MortgageFormSubmission::valid()->count();
            $today = MortgageFormSubmission::valid()->today()->count();
            
            return response()->json([
                'total' => $total,
                'today' => $today,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('獲取統計錯誤', ['error' => $e->getMessage()]);
            
            return response()->json([
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * 通知業務團隊
     */
    private function notifySalesTeam($submissionId)
    {
        try {
            $submission = MortgageFormSubmission::find($submissionId);
            
            if (!$submission) return;

            // 這裡可以添加分配業務員的邏輯
            // 暫時使用第一個活躍的業務員
            $sales = SalesLineAccount::active()->first();
            
            if ($sales) {
                $submission->update(['assigned_to' => $sales->sales_code]);
                
                // 發送 LINE 通知（需要在 LineBotController 中實現）
                app(LineBotController::class)->sendNotification(
                    $sales->line_user_id,
                    $this->formatNotificationMessage($submission)
                );
            }

        } catch (\Exception $e) {
            Log::error('通知業務團隊錯誤', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 格式化通知消息
     */
    private function formatNotificationMessage($submission)
    {
        return "🔔 新客戶通知\n\n" .
               "👤 {$submission->customer_name}\n" .
               "📞 {$submission->phone}\n" .
               "📍 {$submission->region}\n" .
               "⏰ " . $submission->submitted_at->format('m/d H:i') . "\n\n" .
               "請盡快聯絡客戶！";
    }
}