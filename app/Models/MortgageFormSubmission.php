<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MortgageFormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'phone',
        'line_id',
        'region',
        'source_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'ip_address',
        'user_agent',
        'referrer_url',
        'status',
        'assigned_to',
        'submitted_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime'
    ];

    // 關聯到業務員
    public function assignedSales()
    {
        return $this->belongsTo(SalesLineAccount::class, 'assigned_to', 'sales_code');
    }

    // 範圍查詢：今日提交
    public function scopeToday($query)
    {
        return $query->whereDate('submitted_at', Carbon::today());
    }

    // 範圍查詢：本週提交
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('submitted_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    // 範圍查詢：有效狀態
    public function scopeValid($query)
    {
        return $query->where('status', '!=', 'duplicate');
    }

    // 範圍查詢：按業務員
    public function scopeForSales($query, $salesCode)
    {
        return $query->where('assigned_to', $salesCode);
    }

    // 搜尋
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('customer_name', 'LIKE', "%{$term}%")
              ->orWhere('phone', 'LIKE', "%{$term}%")
              ->orWhere('line_id', 'LIKE', "%{$term}%");
        });
    }
}