<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesLineAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_code',
        'sales_name',
        'line_user_id',
        'is_manager',
        'responsible_regions',
        'is_active'
    ];

    protected $casts = [
        'responsible_regions' => 'array',
        'is_manager' => 'boolean',
        'is_active' => 'boolean'
    ];

    // 關聯到表單提交
    public function submissions()
    {
        return $this->hasMany(MortgageFormSubmission::class, 'assigned_to', 'sales_code');
    }

    // 範圍查詢：活躍的業務員
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // 範圍查詢：主管
    public function scopeManagers($query)
    {
        return $query->where('is_manager', true);
    }

    // 通過 LINE ID 查找業務員
    public static function findByLineId($lineUserId)
    {
        return static::where('line_user_id', $lineUserId)
                    ->where('is_active', true)
                    ->first();
    }
}