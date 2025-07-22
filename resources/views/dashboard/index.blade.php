@extends('layouts.app')

@section('title', '管理儀表板')

@section('content')
<div class="header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1><i class="fas fa-tachometer-alt"></i> 管理儀表板</h1>
            <p>歡迎 {{ $user['name'] }} ({{ $user['role'] }})</p>
        </div>
        <div>
            <a href="{{ route('logout') }}" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> 登出
            </a>
        </div>
    </div>
</div>

<div class="card">
    <h3>功能選單</h3>
    
    <div class="menu-grid">
        <a href="{{ route('dashboard.submissions') }}" class="menu-item">
            <div class="menu-icon">📋</div>
            <div class="menu-title">表單提交記錄</div>
            <div class="menu-desc">查看所有客戶提交的表單資料</div>
        </a>

        @if(in_array('view_all', $user['permissions']))
        <a href="{{ route('dashboard.sales-accounts') }}" class="menu-item">
            <div class="menu-icon">👥</div>
            <div class="menu-title">業務員帳號</div>
            <div class="menu-desc">管理 LINE Bot 業務員帳號</div>
        </a>

        <a href="{{ route('dashboard.query-logs') }}" class="menu-item">
            <div class="menu-icon">📊</div>
            <div class="menu-title">查詢日誌</div>
            <div class="menu-desc">LINE Bot 查詢操作記錄</div>
        </a>
        @endif

        <a href="{{ route('dashboard.statistics') }}" class="menu-item">
            <div class="menu-icon">📈</div>
            <div class="menu-title">統計報表</div>
            <div class="menu-desc">客戶數據統計分析</div>
        </a>
    </div>
</div>

<div class="card">
    <h3>權限說明</h3>
    <div class="permission-info">
        <div class="permission-item">
            <strong>您的身份：</strong>{{ $user['name'] }} ({{ $user['role'] }})
        </div>
        <div class="permission-item">
            <strong>可用權限：</strong>
            <ul>
                @foreach($user['permissions'] as $permission)
                    <li>
                        @switch($permission)
                            @case('view_all')
                                查看所有資料
                                @break
                            @case('edit_all')
                                編輯所有資料
                                @break
                            @case('delete_all')
                                刪除所有資料
                                @break
                            @case('view_own')
                                查看自己的資料
                                @break
                            @case('view_dealer')
                                查看經銷商區域資料
                                @break
                            @case('edit_sales')
                                編輯業務資料
                                @break
                            @default
                                {{ $permission }}
                        @endswitch
                    </li>
                @endforeach
            </ul>
        </div>
        
        @if($user['sales_code'])
        <div class="permission-item">
            <strong>業務代碼：</strong>{{ $user['sales_code'] }}
        </div>
        @endif
        
        @if($user['dealer_code'])
        <div class="permission-item">
            <strong>經銷商代碼：</strong>{{ $user['dealer_code'] }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .menu-item {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 25px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
        display: block;
    }

    .menu-item:hover {
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        text-decoration: none;
        color: #333;
    }

    .menu-icon {
        font-size: 48px;
        text-align: center;
        margin-bottom: 15px;
    }

    .menu-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 8px;
        text-align: center;
    }

    .menu-desc {
        font-size: 14px;
        color: #666;
        text-align: center;
        line-height: 1.4;
    }

    .permission-info {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-top: 15px;
    }

    .permission-item {
        margin-bottom: 15px;
    }

    .permission-item:last-child {
        margin-bottom: 0;
    }

    .permission-item ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }

    .permission-item li {
        margin-bottom: 5px;
        color: #28a745;
    }
</style>
@endsection