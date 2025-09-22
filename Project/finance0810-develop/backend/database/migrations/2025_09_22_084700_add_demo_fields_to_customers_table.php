<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 新增客戶表的隱藏欄位，用於全螢幕編輯彈窗
     * 列表只顯示基本資訊：案件狀態、時間、承辦業務、來源管道、姓名、LINE資訊、諮詢項目、操作
     * 隱藏欄位只在編輯彈窗中顯示，分為 5 大區塊
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // 新增案件狀態欄位 (放在最左邊)
            $table->enum('case_status_display', [
                'valid_customer',      // 有效客
                'invalid_customer',    // 無效客
                'customer_service',    // 客服
                'blacklist',          // 黑名單
                'approved_disbursed', // 核准撥款
                'approved_undisbursed', // 核准未撥
                'conditional_approval', // 附條件
                'rejected',           // 婉拒
                'tracking_management' // 追蹤管理 (手動設定)
            ])->default('valid_customer')->comment('案件狀態顯示');

            // 基本資訊 - 新增姓名欄位 (如果還沒有的話，customers 表已有 name)
            $table->string('consultation_item')->nullable()->comment('諮詢項目');
            $table->string('line_add_friend_id')->nullable()->comment('LINE 加好友 ID');

            // === 隱藏欄位區塊 1: 個人資料 ===
            $table->date('birth_date')->nullable()->comment('出生年月日');
            $table->string('id_number', 20)->nullable()->comment('身份證字號');
            $table->string('education')->nullable()->comment('最高學歷');

            // === 隱藏欄位區塊 2: 聯絡資訊 ===
            $table->string('home_address')->nullable()->comment('戶籍地址');
            $table->string('landline_phone', 20)->nullable()->comment('室內電話');
            $table->boolean('comm_address_same_as_home')->default(false)->comment('通訊地址是否同戶籍地');
            $table->string('comm_address')->nullable()->comment('通訊地址');
            $table->string('comm_phone', 20)->nullable()->comment('通訊電話');
            $table->string('residence_duration')->nullable()->comment('現居地住多久');
            $table->string('residence_owner')->nullable()->comment('居住地持有人');
            $table->string('telecom_operator')->nullable()->comment('電信業者');

            // === 隱藏欄位區塊 3: 公司資料 ===
            $table->string('company_name')->nullable()->comment('公司名稱');
            $table->string('company_phone', 20)->nullable()->comment('公司電話');
            $table->string('company_address')->nullable()->comment('公司地址');
            $table->string('job_title')->nullable()->comment('職稱');
            $table->decimal('monthly_income', 12, 2)->nullable()->comment('月收入');
            $table->boolean('has_labor_insurance')->default(false)->comment('有無新轉勞保');
            $table->string('company_tenure')->nullable()->comment('目前公司在職多久');

            // === 隱藏欄位區塊 4: 其他資訊 ===
            $table->decimal('demand_amount', 12, 2)->nullable()->comment('需求金額');
            $table->text('custom_field')->nullable()->comment('自定義欄位');

            // === 隱藏欄位區塊 5: 緊急聯絡人 ===
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

            // 索引
            $table->index(['case_status_display', 'created_at']);
            $table->index(['id_number']);
        });
    }

    /**
     * 回滾 migration
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'case_status_display',
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
                'referrer'
            ]);
        });
    }
};
