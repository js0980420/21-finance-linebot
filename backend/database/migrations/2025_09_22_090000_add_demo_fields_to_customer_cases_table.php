<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 新增案件表的Demo欄位，重新設計案件狀態系統
     *
     * 10種案件狀態設計：
     *
     * 網路進線區塊 (1種):
     * 1. unassigned - 未指派
     *
     * 網路進線管理區塊 (4種):
     * 2. valid_customer - 有效客
     * 3. invalid_customer - 無效客
     * 4. customer_service - 客服
     * 5. blacklist - 黑名單
     *
     * 送件管理區塊 (4種):
     * 6. approved_disbursed - 核准撥款
     * 7. approved_undisbursed - 核准未撥
     * 8. conditional_approval - 附條件
     * 9. rejected - 婉拒
     *
     * 業務管理區塊 (1種):
     * 10. tracking_management - 追蹤管理
     */
    public function up(): void
    {
        Schema::table('customer_cases', function (Blueprint $table) {
            // 重新定義案件狀態枚舉為10種
            DB::statement("ALTER TABLE customer_cases MODIFY COLUMN status ENUM(
                'unassigned',
                'valid_customer',
                'invalid_customer',
                'customer_service',
                'blacklist',
                'approved_disbursed',
                'approved_undisbursed',
                'conditional_approval',
                'rejected',
                'tracking_management'
            ) DEFAULT 'unassigned'");
        });

        // 檢查並新增欄位
        if (!Schema::hasColumn('customer_cases', 'consultation_item')) {
            Schema::table('customer_cases', function (Blueprint $table) {
                // 新增Demo基本資訊欄位 - 比照客戶Demo結構
                $table->string('consultation_item')->nullable()->comment('諮詢項目');
                $table->string('line_add_friend_id')->nullable()->comment('LINE 加好友 ID');

            // === 個人資料區塊 ===
            $table->date('birth_date')->nullable()->comment('出生年月日');
            $table->string('id_number', 20)->nullable()->comment('身份證字號');
            $table->string('education')->nullable()->comment('最高學歷');

            // === 聯絡資訊區塊 ===
            $table->string('home_address')->nullable()->comment('戶籍地址');
            $table->string('landline_phone', 20)->nullable()->comment('室內電話');
            $table->boolean('comm_address_same_as_home')->default(false)->comment('通訊地址是否同戶籍地');
            $table->string('comm_address')->nullable()->comment('通訊地址');
            $table->string('comm_phone', 20)->nullable()->comment('通訊電話');
            $table->string('residence_duration')->nullable()->comment('現居地住多久');
            $table->string('residence_owner')->nullable()->comment('居住地持有人');
            $table->string('telecom_operator')->nullable()->comment('電信業者');

            // === 公司資料區塊 ===
            $table->string('company_name')->nullable()->comment('公司名稱');
            $table->string('company_phone', 20)->nullable()->comment('公司電話');
            $table->string('company_address')->nullable()->comment('公司地址');
            $table->string('job_title')->nullable()->comment('職稱');
            $table->decimal('monthly_income', 12, 2)->nullable()->comment('月收入');
            $table->boolean('has_labor_insurance')->default(false)->comment('有無新轉勞保');
            $table->string('company_tenure')->nullable()->comment('目前公司在職多久');

            // === 其他資訊區塊 ===
            $table->decimal('demand_amount', 12, 2)->nullable()->comment('需求金額');
            $table->text('custom_field')->nullable()->comment('自定義欄位');

            // === 緊急聯絡人區塊 ===
            $table->string('emergency_contact_1_name')->nullable()->comment('聯絡人①姓名');
            $table->string('emergency_contact_1_relationship')->nullable()->comment('聯絡人①關係');
            $table->string('emergency_contact_1_phone', 20)->nullable()->comment('聯絡人①電話');
            $table->string('contact_time_1')->nullable()->comment('聯絡人①方便聯絡時間');
            $table->boolean('confidential_1')->default(false)->comment('聯絡人①是否保密');

            $table->string('emergency_contact_2_name')->nullable()->comment('聯絡人②姓名');
            $table->string('emergency_contact_2_relationship')->nullable()->comment('聯絡人②關係');
            $table->string('emergency_contact_2_phone', 20)->nullable()->comment('聯絡人②電話');
            $table->string('contact_time_2')->nullable()->comment('聯絡人②方便聯絡時間');
            $table->boolean('confidential_2')->default(false)->comment('聯絡人②是否保密');

            $table->string('referrer')->nullable()->comment('介紹人');

            // === Demo 專用基本資訊欄位 ===
            $table->string('customer_name')->nullable()->comment('客戶姓名');
            $table->string('customer_phone', 20)->nullable()->comment('客戶手機');
            $table->string('customer_email')->nullable()->comment('客戶Email');
            $table->string('customer_region')->nullable()->comment('客戶地區');
            $table->string('website_source')->nullable()->comment('網站來源');
            $table->string('channel')->nullable()->comment('來源管道');
            $table->string('line_display_name')->nullable()->comment('LINE 顯示名稱');
            $table->string('line_user_id')->nullable()->comment('LINE 用戶 ID');

            // === 案件管理相關欄位 ===
            $table->timestamp('assigned_at')->nullable()->comment('指派時間');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->comment('指派業務');
            $table->text('status_note')->nullable()->comment('狀態備註');
            $table->timestamp('status_updated_at')->nullable()->comment('狀態更新時間');
            $table->foreignId('status_updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('狀態更新人');

            // 索引 - 檢查是否已存在避免重複
            $indexExists = \DB::select("SHOW INDEX FROM customer_cases WHERE Key_name = 'customer_cases_assigned_to_status_index'");
            if (empty($indexExists)) {
                $table->index(['assigned_to', 'status']);
            }

                $table->index(['id_number']);
                $table->index(['customer_phone']);
                $table->index(['line_user_id']);
                $table->index(['status_updated_at']);
            });
        }
    }

    /**
     * 回滾 migration
     */
    public function down(): void
    {
        Schema::table('customer_cases', function (Blueprint $table) {
            // 恢復原有的案件狀態枚舉
            DB::statement("ALTER TABLE customer_cases MODIFY COLUMN status ENUM('submitted', 'approved', 'rejected', 'disbursed') DEFAULT 'submitted'");

            $table->dropColumn([
                'consultation_item',
                'line_add_friend_id',
                'birth_date',
                'id_number',
                'education',
                'home_address',
                'landline_phone',
                'comm_address_same_as_home',
                'comm_address',
                'comm_phone',
                'residence_duration',
                'residence_owner',
                'telecom_operator',
                'company_name',
                'company_phone',
                'company_address',
                'job_title',
                'monthly_income',
                'has_labor_insurance',
                'company_tenure',
                'demand_amount',
                'custom_field',
                'emergency_contact_1_name',
                'emergency_contact_1_relationship',
                'emergency_contact_1_phone',
                'contact_time_1',
                'confidential_1',
                'emergency_contact_2_name',
                'emergency_contact_2_relationship',
                'emergency_contact_2_phone',
                'contact_time_2',
                'confidential_2',
                'referrer',
                'customer_name',
                'customer_phone',
                'customer_email',
                'customer_region',
                'website_source',
                'channel',
                'line_display_name',
                'line_user_id',
                'assigned_at',
                'assigned_to',
                'status_note',
                'status_updated_at',
                'status_updated_by'
            ]);
        });
    }
};