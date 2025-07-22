<?php

namespace App\Http\Controllers;

use App\Models\MortgageFormSubmission;
use App\Models\SalesLineAccount;
use App\Models\LineQueryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use Carbon\Carbon;

class LineBotController extends Controller
{
    private $bot;

    public function __construct()
    {
        $httpClient = new CurlHTTPClient(config('services.line.access_token'));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => config('services.line.secret')]);
    }

    /**
     * LINE Bot Webhook
     */
    public function webhook(Request $request)
    {
        try {
            $signature = $request->header('x-line-signature');
            $body = $request->getContent();
            
            // 驗證簽名
            if (!$this->validateSignature($body, $signature)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $events = json_decode($body, true)['events'];

            foreach ($events as $event) {
                if ($event['type'] === 'message' && $event['message']['type'] === 'text') {
                    $this->handleLineMessage($event);
                }
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('LINE Webhook 錯誤', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * 處理LINE訊息
     */
    private function handleLineMessage($event)
    {
        $replyToken = $event['replyToken'];
        $userId = $event['source']['userId'];
        $messageText = trim($event['message']['text']);

        Log::info("收到LINE訊息", ['user' => $userId, 'message' => $messageText]);

        try {
            // 記錄查詢日誌
            LineQueryLog::logQuery($userId, 'message', ['text' => $messageText]);

            // 檢查是否為業務員
            $salesInfo = SalesLineAccount::findByLineId($userId);
            
            if ($salesInfo) {
                $this->handleSalesMessage($replyToken, $salesInfo, $messageText);
            } else {
                $this->handleCustomerMessage($replyToken, $messageText);
            }

        } catch (\Exception $e) {
            Log::error('處理LINE訊息錯誤', ['error' => $e->getMessage()]);
            $this->replyMessage($replyToken, '系統暫時忙碌，請稍後再試');
        }
    }

    /**
     * 處理業務員訊息
     */
    private function handleSalesMessage($replyToken, $salesInfo, $messageText)
    {
        switch (true) {
            case in_array($messageText, ['客戶列表', '名單']):
                $submissions = $this->getSubmissionsForSales($salesInfo);
                $replyText = $this->formatSubmissionsList($submissions, $salesInfo->sales_name);
                break;

            case in_array($messageText, ['今日客戶', '今天']):
                $submissions = $this->getTodaySubmissions($salesInfo);
                $replyText = $this->formatTodaySubmissions($submissions);
                break;

            case in_array($messageText, ['統計', '報表']):
                $stats = $this->getSubmissionStats($salesInfo);
                $replyText = $this->formatStatsReport($stats, $salesInfo->sales_name);
                break;

            case (strpos($messageText, '搜尋:') === 0 || strpos($messageText, '客戶:') === 0):
                $searchTerm = trim(str_replace(['搜尋:', '客戶:'], '', $messageText));
                $results = $this->searchSubmissions($searchTerm, $salesInfo);
                $replyText = $this->formatSearchResults($results, $searchTerm);
                break;

            default:
                $replyText = $this->getSalesMenu($salesInfo);
        }

        $this->replyMessage($replyToken, $replyText);
    }

    /**
     * 處理一般客戶訊息
     */
    private function handleCustomerMessage($replyToken, $messageText)
    {
        $menuText = "歡迎使用房貸先生服務！\n\n" .
                   "我們提供：\n" .
                   "🏠 房屋貸款諮詢\n" .
                   "💰 二胎房貸服務\n" .
                   "📞 專業諮詢：0800-123-456\n\n" .
                   "如需填寫貸款需求表單，\n" .
                   "請至我們的官網：\n" .
                   "https://your-mortgage-site.com";

        $this->replyMessage($replyToken, $menuText);
    }

    /**
     * 獲取業務員負責的提交記錄
     */
    private function getSubmissionsForSales($salesInfo)
    {
        $query = MortgageFormSubmission::valid()->orderBy('submitted_at', 'desc')->limit(20);
        
        if (!$salesInfo->is_manager) {
            $query->forSales($salesInfo->sales_code);
        }

        return $query->get();
    }

    /**
     * 獲取今日提交記錄
     */
    private function getTodaySubmissions($salesInfo)
    {
        $query = MortgageFormSubmission::valid()->today()->orderBy('submitted_at', 'desc');
        
        if (!$salesInfo->is_manager) {
            $query->forSales($salesInfo->sales_code);
        }

        return $query->get();
    }

    /**
     * 搜尋提交記錄
     */
    private function searchSubmissions($searchTerm, $salesInfo)
    {
        $query = MortgageFormSubmission::valid()->search($searchTerm)->orderBy('submitted_at', 'desc')->limit(10);
        
        if (!$salesInfo->is_manager) {
            $query->forSales($salesInfo->sales_code);
        }

        return $query->get();
    }

    /**
     * 獲取統計資料
     */
    private function getSubmissionStats($salesInfo)
    {
        $baseQuery = MortgageFormSubmission::valid();
        
        if (!$salesInfo->is_manager) {
            $baseQuery->forSales($salesInfo->sales_code);
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'today' => (clone $baseQuery)->today()->count(),
            'thisWeek' => (clone $baseQuery)->thisWeek()->count(),
            'contacted' => (clone $baseQuery)->whereIn('status', ['contacted', 'qualified'])->count()
        ];
    }

    /**
     * 格式化業務員選單
     */
    private function getSalesMenu($salesInfo)
    {
        return "您好 {$salesInfo->sales_name}！\n\n" .
               "📋 客戶列表 - 查看負責客戶\n" .
               "📅 今日客戶 - 查看今天新增\n" .
               "📊 統計 - 查看統計報表\n" .
               "🔍 搜尋:客戶名稱 - 搜尋客戶\n\n" .
               "負責區域：" . implode('、', $salesInfo->responsible_regions);
    }

    /**
     * 格式化客戶列表
     */
    private function formatSubmissionsList($submissions, $salesName)
    {
        if ($submissions->isEmpty()) {
            return "{$salesName}，目前沒有客戶資料。";
        }

        $text = "📋 {$salesName} 的客戶列表\n\n";
        
        foreach ($submissions as $index => $sub) {
            $datetime = $sub->submitted_at->format('m/d H:i');
            $lineIdText = $sub->line_id ? "\n📱 LINE: {$sub->line_id}" : '';
            
            $text .= ($index + 1) . ". {$sub->customer_name}\n";
            $text .= "📞 {$sub->phone}\n";
            $text .= "📍 {$sub->region}{$lineIdText}\n";
            $text .= "⏰ {$datetime} | {$sub->status}\n\n";
        }

        return substr($text, 0, 2000); // LINE訊息長度限制
    }

    /**
     * 格式化今日客戶
     */
    private function formatTodaySubmissions($submissions)
    {
        if ($submissions->isEmpty()) {
            return '📅 今日暫無新客戶。';
        }

        $text = "📅 今日新客戶 ({$submissions->count()}筆)\n\n";
        
        foreach ($submissions as $index => $sub) {
            $time = $sub->submitted_at->format('H:i');
            $lineIdText = $sub->line_id ? " | LINE: {$sub->line_id}" : '';
            
            $text .= ($index + 1) . ". {$sub->customer_name}\n";
            $text .= "📞 {$sub->phone} | 📍 {$sub->region}{$lineIdText}\n";
            $text .= "⏰ {$time}\n\n";
        }

        return substr($text, 0, 2000);
    }

    /**
     * 格式化搜尋結果
     */
    private function formatSearchResults($results, $searchTerm)
    {
        if ($results->isEmpty()) {
            return "🔍 找不到「{$searchTerm}」的相關客戶資料。";
        }

        $text = "🔍 搜尋「{$searchTerm}」結果\n\n";
        
        foreach ($results as $index => $sub) {
            $datetime = $sub->submitted_at->format('m/d H:i');
            $lineIdText = $sub->line_id ? " | LINE: {$sub->line_id}" : '';
            
            $text .= ($index + 1) . ". {$sub->customer_name}\n";
            $text .= "📞 {$sub->phone} | 📍 {$sub->region}{$lineIdText}\n";
            $text .= "⏰ {$datetime} | {$sub->status}\n\n";
        }

        return substr($text, 0, 2000);
    }

    /**
     * 格式化統計報表
     */
    private function formatStatsReport($stats, $salesName)
    {
        return "📊 {$salesName} 統計報表\n\n" .
               "📋 總客戶數：{$stats['total']}\n" .
               "📅 今日新增：{$stats['today']}\n" .
               "📆 本週新增：{$stats['thisWeek']}\n" .
               "✅ 已聯絡：{$stats['contacted']}\n\n" .
               "更新時間：" . now()->format('m/d H:i');
    }

    /**
     * 發送通知
     */
    public function sendNotification($lineUserId, $message)
    {
        try {
            $textMessageBuilder = new TextMessageBuilder($message);
            $response = $this->bot->pushMessage($lineUserId, $textMessageBuilder);
            
            if (!$response->isSucceeded()) {
                Log::error('發送LINE通知失敗', ['response' => $response->getJSONDecodedBody()]);
            }
        } catch (\Exception $e) {
            Log::error('發送LINE通知錯誤', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 回覆訊息
     */
    private function replyMessage($replyToken, $message)
    {
        try {
            $textMessageBuilder = new TextMessageBuilder($message);
            $response = $this->bot->replyMessage($replyToken, $textMessageBuilder);
            
            if (!$response->isSucceeded()) {
                Log::error('回覆LINE訊息失敗', ['response' => $response->getJSONDecodedBody()]);
            }
        } catch (\Exception $e) {
            Log::error('回覆LINE訊息錯誤', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 驗證簽名
     */
    private function validateSignature($body, $signature)
    {
        $hash = base64_encode(hash_hmac('sha256', $body, config('services.line.secret'), true));
        return hash_equals($signature, $hash);
    }
}