<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerCase extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_id',
        'lead_id',
        'case_number',
        'loan_amount',
        'loan_type',
        'loan_term',
        'interest_rate',
        'status',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'disbursed_at',
        'approved_amount',
        'disbursed_amount',
        'rejection_reason',
        'notes',
        'documents',
        'created_by',

        // Demo 新增欄位 - 基本資訊
        'consultation_item',
        'line_add_friend_id',

        // Demo 新增欄位 - 個人資料
        'birth_date',
        'id_number',
        'education',

        // Demo 新增欄位 - 聯絡資訊
        'home_address',
        'landline_phone',
        'comm_address_same_as_home',
        'comm_address',
        'comm_phone',
        'residence_duration',
        'residence_owner',
        'telecom_operator',

        // Demo 新增欄位 - 公司資料
        'company_name',
        'company_phone',
        'company_address',
        'job_title',
        'monthly_income',
        'has_labor_insurance',
        'company_tenure',

        // Demo 新增欄位 - 其他資訊
        'demand_amount',
        'custom_field',

        // Demo 新增欄位 - 緊急聯絡人
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

        // Demo 新增欄位 - 客戶基本資訊
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_region',
        'website_source',
        'channel',
        'line_display_name',
        'line_user_id',

        // Demo 新增欄位 - 案件管理
        'assigned_at',
        'assigned_to',
        'status_note',
        'status_updated_at',
        'status_updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'loan_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'documents' => 'array',

        // Demo 新增欄位的類型轉換
        'birth_date' => 'date',
        'monthly_income' => 'decimal:2',
        'demand_amount' => 'decimal:2',
        'comm_address_same_as_home' => 'boolean',
        'has_labor_insurance' => 'boolean',
        'confidential_1' => 'boolean',
        'confidential_2' => 'boolean',
        'assigned_at' => 'datetime',
        'status_updated_at' => 'datetime',
    ];

    /**
     * 案件狀態常數 - 10種狀態系統
     */
    // 網路進線區塊 (1種)
    const STATUS_UNASSIGNED = 'unassigned';                    // 未指派

    // 網路進線管理區塊 (4種)
    const STATUS_VALID_CUSTOMER = 'valid_customer';            // 有效客
    const STATUS_INVALID_CUSTOMER = 'invalid_customer';        // 無效客
    const STATUS_CUSTOMER_SERVICE = 'customer_service';        // 客服
    const STATUS_BLACKLIST = 'blacklist';                      // 黑名單

    // 送件管理區塊 (4種)
    const STATUS_APPROVED_DISBURSED = 'approved_disbursed';    // 核准撥款
    const STATUS_APPROVED_UNDISBURSED = 'approved_undisbursed'; // 核准未撥
    const STATUS_CONDITIONAL_APPROVAL = 'conditional_approval'; // 附條件
    const STATUS_REJECTED = 'rejected';                        // 婉拒

    // 業務管理區塊 (1種)
    const STATUS_TRACKING_MANAGEMENT = 'tracking_management';  // 追蹤管理

    /**
     * 取得案件狀態顯示名稱對應
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_UNASSIGNED => '未指派',
            self::STATUS_VALID_CUSTOMER => '有效客',
            self::STATUS_INVALID_CUSTOMER => '無效客',
            self::STATUS_CUSTOMER_SERVICE => '客服',
            self::STATUS_BLACKLIST => '黑名單',
            self::STATUS_APPROVED_DISBURSED => '核准撥款',
            self::STATUS_APPROVED_UNDISBURSED => '核准未撥',
            self::STATUS_CONDITIONAL_APPROVAL => '附條件',
            self::STATUS_REJECTED => '婉拒',
            self::STATUS_TRACKING_MANAGEMENT => '追蹤管理',
        ];
    }

    /**
     * 取得案件狀態分組
     */
    public static function getStatusGroups(): array
    {
        return [
            '網路進線' => [
                self::STATUS_UNASSIGNED => '未指派',
            ],
            '網路進線管理' => [
                self::STATUS_VALID_CUSTOMER => '有效客',
                self::STATUS_INVALID_CUSTOMER => '無效客',
                self::STATUS_CUSTOMER_SERVICE => '客服',
                self::STATUS_BLACKLIST => '黑名單',
            ],
            '送件管理' => [
                self::STATUS_APPROVED_DISBURSED => '核准撥款',
                self::STATUS_APPROVED_UNDISBURSED => '核准未撥',
                self::STATUS_CONDITIONAL_APPROVAL => '附條件',
                self::STATUS_REJECTED => '婉拒',
            ],
            '業務管理' => [
                self::STATUS_TRACKING_MANAGEMENT => '追蹤管理',
            ],
        ];
    }

    /**
     * 取得 6 大區塊欄位分組 (比照客戶Demo結構)
     */
    public static function getFieldGroups(): array
    {
        return [
            '基本資訊' => [
                'status', 'created_at', 'assigned_to', 'channel',
                'customer_name', 'line_display_name', 'line_add_friend_id',
                'consultation_item', 'website_source', 'customer_email', 'customer_phone'
            ],
            '個人資料' => [
                'birth_date', 'id_number', 'education'
            ],
            '聯絡資訊' => [
                'customer_region', 'home_address', 'landline_phone', 'comm_address_same_as_home',
                'comm_address', 'comm_phone', 'residence_duration', 'residence_owner', 'telecom_operator'
            ],
            '公司資料' => [
                'company_name', 'company_phone', 'company_address', 'job_title',
                'monthly_income', 'has_labor_insurance', 'company_tenure'
            ],
            '其他資訊' => [
                'demand_amount', 'custom_field', 'notes', 'loan_amount', 'loan_type', 'loan_term', 'interest_rate'
            ],
            '緊急聯絡人' => [
                'emergency_contact_1_name', 'emergency_contact_1_relationship', 'emergency_contact_1_phone',
                'contact_time_1', 'confidential_1', 'emergency_contact_2_name', 'emergency_contact_2_relationship',
                'emergency_contact_2_phone', 'contact_time_2', 'confidential_2', 'referrer'
            ]
        ];
    }

    /**
     * Get the customer that owns this case
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the lead this case originated from (optional)
     */
    public function lead()
    {
        return $this->belongsTo(\App\Models\CustomerLead::class, 'lead_id');
    }

    /**
     * Get the user who created this case
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to this case
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who last updated the status
     */
    public function statusUpdater()
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }

    /**
     * Generate unique case number
     */
    public static function generateCaseNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $sequence = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;
        
        return sprintf('%s%s%04d', $year, $month, $sequence);
    }
}