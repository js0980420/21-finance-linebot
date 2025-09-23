<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomerCase;
use App\Models\User;

class CaseSeeder extends Seeder
{
    /**
     * 生成案件假數據，涵蓋 10 種狀態
     */
    public function run(): void
    {
        // 確保有用戶可以指派
        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->error('請先創建用戶再運行案件 Seeder');
            return;
        }

        $user = $users->first();

        // 10種狀態的案件數據
        $casesData = [
            // 1. 未指派 (網路進線)
            [
                'customer_name' => '張小明',
                'customer_phone' => '0912345678',
                'customer_email' => 'zhang@example.com',
                'customer_region' => '台北市中山區',
                'website_source' => 'https://finance-demo.com',
                'channel' => 'wp',
                'consultation_item' => '房屋貸款',
                'demand_amount' => 1500000,
                'loan_amount' => null,
                'status' => 'unassigned',
                'notes' => '透過網站表單進線，需要房屋貸款',
                'created_by' => $user->id,
            ],
            [
                'customer_name' => '李美華',
                'customer_phone' => '0923456789',
                'customer_email' => 'li@example.com',
                'customer_region' => '新北市板橋區',
                'website_source' => 'https://finance-demo.com',
                'channel' => 'lineoa',
                'consultation_item' => '信用貸款',
                'demand_amount' => 500000,
                'loan_amount' => null,
                'status' => 'unassigned',
                'notes' => '透過 LINE 官方帳號進線',
                'created_by' => $user->id,
            ],

            // 2. 有效客 (網路進線管理)
            [
                'customer_name' => '王大成',
                'customer_phone' => '0934567890',
                'customer_email' => 'wang@example.com',
                'customer_region' => '台中市西屯區',
                'website_source' => 'https://finance-demo.com',
                'channel' => 'wp',
                'consultation_item' => '企業貸款',
                'demand_amount' => 3000000,
                'loan_amount' => null,
                'status' => 'valid_customer',
                'assigned_to' => $user->id,
                'assigned_at' => now()->subDays(2),
                'status_updated_at' => now()->subDays(2),
                'status_updated_by' => $user->id,
                'notes' => '已確認為有效客戶，準備進入審核流程',
                'created_by' => $user->id,
            ],

            // 3. 無效客 (網路進線管理)
            [
                'customer_name' => '陳小江',
                'customer_phone' => '0945678901',
                'customer_email' => 'chen@example.com',
                'customer_region' => '高雄市前金區',
                'website_source' => 'https://finance-demo.com',
                'channel' => 'phone',
                'consultation_item' => '汽車貸款',
                'demand_amount' => 800000,
                'status' => 'invalid_customer',
                'assigned_to' => $user->id,
                'assigned_at' => now()->subDays(1),
                'status_updated_at' => now()->subDays(1),
                'status_updated_by' => $user->id,
                'notes' => '無穩定收入證明，暫列為無效客戶',
                'created_by' => $user->id,
            ],

            // 4. 客服 (網路進線管理)
            [
                'customer_name' => '林雅婷',
                'customer_phone' => '0956789012',
                'customer_email' => 'lin@example.com',
                'customer_region' => '桃園市中壢區',
                'website_source' => 'https://finance-demo.com',
                'channel' => 'email',
                'consultation_item' => '房屋增貸',
                'demand_amount' => 1200000,
                'status' => 'customer_service',
                'assigned_to' => $user->id,
                'assigned_at' => now()->subHours(6),
                'status_updated_at' => now()->subHours(6),
                'status_updated_by' => $user->id,
                'notes' => '需要客服進一步確認貸款條件',
                'created_by' => $user->id,
            ],

            // 5. 黑名單 (網路進線管理)
            [
                'customer_name' => '劉小偉',
                'customer_phone' => '0967890123',
                'customer_email' => 'liu@example.com',
                'customer_region' => '台南市安南區',
                'website_source' => 'https://finance-demo.com',
                'channel' => 'wp',
                'consultation_item' => '代書貸款',
                'demand_amount' => 200000,
                'status' => 'blacklist',
                'assigned_to' => $user->id,
                'assigned_at' => now()->subDays(3),
                'status_updated_at' => now()->subDays(3),
                'status_updated_by' => $user->id,
                'notes' => '信用不良，列入黑名單',
                'created_by' => $user->id,
            ],

            // 6. 核准撥款 (送件管理)
            [
                'customer_name' => '蔡志明',
                'customer_phone' => '0978901234',
                'customer_email' => 'tsai@example.com',
                'customer_region' => '新竹市東區',
                'website_source' => 'https://finance-demo.com',
                'channel' => 'wp',
                'consultation_item' => '房屋貸款',
                'demand_amount' => 2500000,
                'loan_amount' => 2000000,
                'approved_amount' => 2000000,
                'disbursed_amount' => 2000000,
                'status' => 'approved_disbursed',
                'assigned_to' => $user->id,
                'assigned_at' => now()->subDays(10),
                'approved_at' => now()->subDays(2),
                'disbursed_at' => now()->subDays(1),
                'status_updated_at' => now()->subDays(1),
                'status_updated_by' => $user->id,
                'notes' => '已完成撥款',
                'created_by' => $user->id,
            ],

            // 7. 核准未撥 (送件管理)
            [
                'customer_name' => '黃麗雯',
                'customer_phone' => '0989012345',
                'customer_email' => 'huang@example.com',
                'customer_region' => '彰化縣彰化市',
                'website_source' => 'https://finance-demo.com',
                'channel' => 'lineoa',
                'consultation_item' => '信用貸款',
                'demand_amount' => 600000,
                'loan_amount' => 500000,
                'approved_amount' => 500000,
                'status' => 'approved_undisbursed',
                'assigned_to' => $user->id,
                'assigned_at' => now()->subDays(7),
                'approved_at' => now()->subDays(1),
                'status_updated_at' => now()->subDays(1),
                'status_updated_by' => $user->id,
                'notes' => '已核准，等待客戶完成撥款手續',
                'created_by' => $user->id,
            ],

            // 8. 附條件 (送件管理)
            [
                'customer_name' => '吳建國',
                'customer_phone' => '0990123456',
                'customer_email' => 'wu@example.com',
                'customer_region' => '雲林縣斗六市',
                'website_source' => 'https://finance-demo.com',
                'channel' => 'phone',
                'consultation_item' => '企業貸款',
                'demand_amount' => 5000000,
                'loan_amount' => 4000000,
                'approved_amount' => 3500000,
                'status' => 'conditional_approval',
                'assigned_to' => $user->id,
                'assigned_at' => now()->subDays(5),
                'approved_at' => now()->subHours(12),
                'status_updated_at' => now()->subHours(12),
                'status_updated_by' => $user->id,
                'notes' => '需補充財務文件，條件核准',
                'created_by' => $user->id,
            ],

            // 9. 婉拒 (送件管理)
            [
                'customer_name' => '鄭文華',
                'customer_phone' => '0901234567',
                'customer_email' => 'zheng@example.com',
                'customer_region' => '嘉義市西區',
                'website_source' => 'https://finance-demo.com',
                'channel' => 'email',
                'consultation_item' => '汽車貸款',
                'demand_amount' => 300000,
                'status' => 'rejected',
                'assigned_to' => $user->id,
                'assigned_at' => now()->subDays(4),
                'rejected_at' => now()->subDays(2),
                'status_updated_at' => now()->subDays(2),
                'status_updated_by' => $user->id,
                'rejection_reason' => '收入不符合貸款條件',
                'notes' => '收入證明不足，予以婉拒',
                'created_by' => $user->id,
            ],

            // 10. 追蹤管理 (業務管理)
            [
                'customer_name' => '許志豪',
                'customer_phone' => '0912345098',
                'customer_email' => 'xu@example.com',
                'customer_region' => '宜蘭縣宜蘭市',
                'website_source' => 'https://finance-demo.com',
                'channel' => 'wp',
                'consultation_item' => '房屋增貸',
                'demand_amount' => 1800000,
                'status' => 'tracking_management',
                'assigned_to' => $user->id,
                'assigned_at' => now()->subDays(8),
                'status_updated_at' => now()->subDays(3),
                'status_updated_by' => $user->id,
                'notes' => '需持續追蹤客戶需求',
                'created_by' => $user->id,
            ],
        ];

        foreach ($casesData as $caseData) {
            // 生成案件編號
            $caseData['case_number'] = CustomerCase::generateCaseNumber();

            // 設置 customer_id 為 null（獨立案件）
            $caseData['customer_id'] = null;

            CustomerCase::create($caseData);
        }

        $this->command->info('已創建 ' . count($casesData) . ' 筆案件假數據，涵蓋所有 10 種狀態');
    }
}