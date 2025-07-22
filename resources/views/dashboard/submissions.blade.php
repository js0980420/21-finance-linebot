@extends('layouts.app')

@section('title', '表單提交記錄')

@section('content')
<div class="header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1><i class="fas fa-clipboard-list"></i> 表單提交記錄</h1>
            <p>查看所有客戶提交的表單資料</p>
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
    <form method="GET" action="{{ route('dashboard.submissions') }}">
        <div class="filter-row">
            <input type="text" name="search" class="form-control" placeholder="搜尋客戶姓名、電話或 LINE ID..." value="{{ request('search') }}">
            
            <select name="status" class="form-control">
                <option value="">所有狀態</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>待處理</option>
                <option value="contacted" {{ request('status') == 'contacted' ? 'selected' : '' }}>已聯絡</option>
                <option value="qualified" {{ request('status') == 'qualified' ? 'selected' : '' }}>已認證</option>
                <option value="invalid" {{ request('status') == 'invalid' ? 'selected' : '' }}>無效</option>
                <option value="duplicate" {{ request('status') == 'duplicate' ? 'selected' : '' }}>重複</option>
            </select>

            <select name="region" class="form-control">
                <option value="">所有區域</option>
                @foreach($regions as $region)
                    <option value="{{ $region }}" {{ request('region') == $region ? 'selected' : '' }}>{{ $region }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" title="開始日期">
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" title="結束日期">

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> 搜尋
            </button>
            
            <a href="{{ route('dashboard.submissions') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> 清除
            </a>
        </div>
    </form>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>資料列表 (共 {{ $submissions->total() }} 筆)</h3>
        <div>
            <span style="color: #666;">每頁 {{ $submissions->perPage() }} 筆，第 {{ $submissions->currentPage() }} / {{ $submissions->lastPage() }} 頁</span>
        </div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>提交時間</th>
                    <th>客戶姓名</th>
                    <th>電話</th>
                    <th>LINE ID</th>
                    <th>區域</th>
                    <th>狀態</th>
                    <th>負責人</th>
                    <th>來源</th>
                    <th>UTM 參數</th>
                    <th>IP 位址</th>
                    <th>建立時間</th>
                    <th>更新時間</th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $submission)
                <tr>
                    <td>{{ $submission->id }}</td>
                    <td>{{ $submission->submitted_at->format('m/d H:i') }}</td>
                    <td><strong>{{ $submission->customer_name }}</strong></td>
                    <td>{{ $submission->phone }}</td>
                    <td>{{ $submission->line_id ?: '-' }}</td>
                    <td>{{ $submission->region }}</td>
                    <td>
                        <span class="status-badge status-{{ $submission->status }}">
                            @switch($submission->status)
                                @case('pending') 待處理 @break
                                @case('contacted') 已聯絡 @break
                                @case('qualified') 已認證 @break
                                @case('invalid') 無效 @break
                                @case('duplicate') 重複 @break
                                @default {{ $submission->status }}
                            @endswitch
                        </span>
                    </td>
                    <td>{{ $submission->assigned_to ?: '-' }}</td>
                    <td>
                        @if($submission->source_url)
                            <a href="{{ $submission->source_url }}" target="_blank" style="font-size: 12px;" title="來源網址">
                                {{ Str::limit($submission->source_url, 30) }}
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td style="font-size: 12px;">
                        @if($submission->utm_source || $submission->utm_medium || $submission->utm_campaign)
                            <div><strong>來源:</strong> {{ $submission->utm_source ?: '-' }}</div>
                            <div><strong>媒介:</strong> {{ $submission->utm_medium ?: '-' }}</div>
                            <div><strong>活動:</strong> {{ $submission->utm_campaign ?: '-' }}</div>
                        @else
                            -
                        @endif
                    </td>
                    <td style="font-size: 12px;">{{ $submission->ip_address ?: '-' }}</td>
                    <td style="font-size: 12px;">{{ $submission->created_at->format('m/d H:i') }}</td>
                    <td style="font-size: 12px;">{{ $submission->updated_at->format('m/d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="13" style="text-align: center; color: #666;">沒有找到符合條件的資料</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        {{ $submissions->links() }}
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
        max-width: 100%;
    }

    .table {
        min-width: 1200px;
        font-size: 13px;
    }

    .table th {
        background-color: #f8f9fa;
        font-weight: bold;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table td {
        vertical-align: top;
        max-width: 200px;
        word-wrap: break-word;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-contacted {
        background-color: #d4edda;
        color: #155724;
    }

    .status-qualified {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .status-invalid {
        background-color: #f8d7da;
        color: #721c24;
    }

    .status-duplicate {
        background-color: #e2e3e5;
        color: #383d41;
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