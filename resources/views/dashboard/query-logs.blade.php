@extends('layouts.app')

@section('title', 'LINE Bot 查詢日誌')

@section('content')
<div class="header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1><i class="fas fa-list-alt"></i> LINE Bot 查詢日誌</h1>
            <p>LINE Bot 查詢操作記錄</p>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> 返回儀表板
            </a>
        </div>
    </div>
</div>

<div class="card">
    <h3>篩選條件</h3>
    <form method="GET" action="{{ route('dashboard.query-logs') }}">
        <div class="filter-row">
            <input type="text" name="line_user_id" class="form-control" placeholder="LINE User ID..." value="{{ request('line_user_id') }}">
            
            <select name="query_type" class="form-control">
                <option value="">所有查詢類型</option>
                <option value="message" {{ request('query_type') == 'message' ? 'selected' : '' }}>訊息查詢</option>
                <option value="customer_list" {{ request('query_type') == 'customer_list' ? 'selected' : '' }}>客戶列表</option>
                <option value="today_customers" {{ request('query_type') == 'today_customers' ? 'selected' : '' }}>今日客戶</option>
                <option value="statistics" {{ request('query_type') == 'statistics' ? 'selected' : '' }}>統計查詢</option>
                <option value="search" {{ request('query_type') == 'search' ? 'selected' : '' }}>搜尋功能</option>
            </select>

            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" title="開始日期">
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" title="結束日期">

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> 搜尋
            </button>
            
            <a href="{{ route('dashboard.query-logs') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> 清除
            </a>
        </div>
    </form>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>查詢記錄 (共 {{ $logs->total() }} 筆)</h3>
        <div>
            <span style="color: #666;">每頁 {{ $logs->perPage() }} 筆，第 {{ $logs->currentPage() }} / {{ $logs->lastPage() }} 頁</span>
        </div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>查詢時間</th>
                    <th>LINE User ID</th>
                    <th>查詢類型</th>
                    <th>查詢參數</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->id }}</td>
                    <td>{{ $log->created_at->format('m/d H:i:s') }}</td>
                    <td style="font-family: monospace; font-size: 12px;">{{ $log->line_user_id }}</td>
                    <td>
                        <span class="query-type-badge">
                            @switch($log->query_type)
                                @case('message') 💬 訊息 @break
                                @case('customer_list') 📋 客戶列表 @break
                                @case('today_customers') 📅 今日客戶 @break
                                @case('statistics') 📊 統計 @break
                                @case('search') 🔍 搜尋 @break
                                @default {{ $log->query_type }}
                            @endswitch
                        </span>
                    </td>
                    <td style="max-width: 300px;">
                        @if($log->query_params)
                            <details>
                                <summary style="cursor: pointer; color: #007bff;">查看參數</summary>
                                <pre style="font-size: 11px; margin-top: 8px; background: #f8f9fa; padding: 8px; border-radius: 4px; white-space: pre-wrap;">{{ json_encode($log->query_params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </details>
                        @else
                            <span style="color: #666;">無參數</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #666;">沒有找到符合條件的查詢記錄</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $logs->links() }}
    </div>
</div>
@endsection

@section('styles')
<style>
    .filter-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-row > * {
        flex: 1;
        min-width: 150px;
    }

    .filter-row button,
    .filter-row a {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .table-container {
        overflow-x: auto;
    }

    .table {
        min-width: 800px;
    }

    .query-type-badge {
        display: inline-block;
        padding: 4px 8px;
        background-color: #e9ecef;
        color: #495057;
        border-radius: 12px;
        font-size: 12px;
        white-space: nowrap;
    }

    details summary {
        outline: none;
    }

    details[open] summary {
        color: #666;
    }

    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
        }
        
        .filter-row > * {
            min-width: auto;
            width: 100%;
        }
    }
</style>
@endsection