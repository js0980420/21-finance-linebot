@extends('layouts.app')

@section('title', '業務員帳號管理')

@section('content')
<div class="header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1><i class="fas fa-users"></i> 業務員帳號管理</h1>
            <p>管理 LINE Bot 業務員帳號</p>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> 返回儀表板
            </a>
        </div>
    </div>
</div>

<div class="card">
    <h3>業務員列表 (共 {{ $salesAccounts->count() }} 個帳號)</h3>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>業務代碼</th>
                    <th>業務員姓名</th>
                    <th>LINE User ID</th>
                    <th>是否為主管</th>
                    <th>負責區域</th>
                    <th>帳號狀態</th>
                    <th>建立時間</th>
                    <th>更新時間</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesAccounts as $account)
                <tr>
                    <td>{{ $account->id }}</td>
                    <td><strong>{{ $account->sales_code }}</strong></td>
                    <td>{{ $account->sales_name }}</td>
                    <td style="font-family: monospace; font-size: 12px;">{{ $account->line_user_id }}</td>
                    <td>
                        @if($account->is_manager)
                            <span class="badge badge-manager">主管</span>
                        @else
                            <span class="badge badge-sales">業務員</span>
                        @endif
                    </td>
                    <td>
                        <div style="max-width: 200px;">
                            @foreach($account->responsible_regions as $region)
                                <span class="region-tag">{{ $region }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        @if($account->is_active)
                            <span class="badge badge-active">啟用</span>
                        @else
                            <span class="badge badge-inactive">停用</span>
                        @endif
                    </td>
                    <td style="font-size: 12px;">{{ $account->created_at->format('Y/m/d H:i') }}</td>
                    <td style="font-size: 12px;">{{ $account->updated_at->format('Y/m/d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #666;">沒有業務員帳號資料</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3>說明</h3>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
        <ul style="margin: 0; padding-left: 20px;">
            <li><strong>業務代碼：</strong>用於系統內部識別業務員身份</li>
            <li><strong>LINE User ID：</strong>業務員在 LINE 平台的唯一識別碼</li>
            <li><strong>主管：</strong>可以查看所有業務員的客戶資料</li>
            <li><strong>業務員：</strong>只能查看自己負責的客戶資料</li>
            <li><strong>負責區域：</strong>業務員負責的服務區域</li>
            <li><strong>帳號狀態：</strong>控制業務員是否可以使用 LINE Bot 功能</li>
        </ul>
    </div>
</div>
@endsection

@section('styles')
<style>
    .table-container {
        overflow-x: auto;
    }

    .table {
        min-width: 800px;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .badge-manager {
        background-color: #dc3545;
        color: white;
    }

    .badge-sales {
        background-color: #007bff;
        color: white;
    }

    .badge-active {
        background-color: #28a745;
        color: white;
    }

    .badge-inactive {
        background-color: #6c757d;
        color: white;
    }

    .region-tag {
        display: inline-block;
        background-color: #e9ecef;
        color: #495057;
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 11px;
        margin: 2px;
        white-space: nowrap;
    }
</style>
@endsection