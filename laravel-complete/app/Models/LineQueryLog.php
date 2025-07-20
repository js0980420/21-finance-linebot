<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineQueryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'line_user_id',
        'query_type',
        'query_params'
    ];

    protected $casts = [
        'query_params' => 'array'
    ];

    // 記錄查詢日誌
    public static function logQuery($lineUserId, $queryType, $queryParams = [])
    {
        return static::create([
            'line_user_id' => $lineUserId,
            'query_type' => $queryType,
            'query_params' => $queryParams
        ]);
    }
}