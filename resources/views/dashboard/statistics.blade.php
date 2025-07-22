@extends('layouts.app')

@section('title', '統計報表')

@section('content')
<div class="header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1><i class="fas fa-chart-bar"></i> 統計報表</h1>
            <p>客戶數據統計分析</p>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> 返回儀表板
            </a>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-number">{{ $stats['total'] }}</div>
        <div class="stat-label">總客戶數</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-number">{{ $stats['today'] }}</div>
        <div class="stat-label">今日新增</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📆</div>
        <div class="stat-number">{{ $stats['this_week'] }}</div>
        <div class="stat-label">本週新增</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">🗓️</div>
        <div class="stat-number">{{ $stats['this_month'] }}</div>
        <div class="stat-label">本月新增</div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card status-pending">
        <div class="stat-icon">⏳</div>
        <div class="stat-number">{{ $stats['pending'] }}</div>
        <div class="stat-label">待處理</div>
    </div>

    <div class="stat-card status-contacted">
        <div class="stat-icon">📞</div>
        <div class="stat-number">{{ $stats['contacted'] }}</div>
        <div class="stat-label">已聯絡</div>
    </div>

    <div class="stat-card status-qualified">
        <div class="stat-icon">✅</div>
        <div class="stat-number">{{ $stats['qualified'] }}</div>
        <div class="stat-label">已認證</div>
    </div>

    <div class="stat-card status-invalid">
        <div class="stat-icon">❌</div>
        <div class="stat-number">{{ $stats['invalid'] }}</div>
        <div class="stat-label">無效客戶</div>
    </div>
</div>

<div class="card">
    <h3>區域分布統計</h3>
    <div class="region-stats">
        @foreach($regionStats as $region)
        <div class="region-item">
            <div class="region-name">{{ $region->region }}</div>
            <div class="region-bar">
                <div class="region-fill" style="width: {{ ($region->count / $regionStats->max('count')) * 100 }}%"></div>
            </div>
            <div class="region-count">{{ $region->count }}</div>
        </div>
        @endforeach
    </div>
</div>

@if($dailyStats->count() > 0)
<div class="card">
    <h3>每日新增趨勢 (最近30天)</h3>
    <div class="daily-chart">
        @foreach($dailyStats as $day)
        <div class="day-item">
            <div class="day-bar" style="height: {{ ($day->count / $dailyStats->max('count')) * 100 }}%"></div>
            <div class="day-count">{{ $day->count }}</div>
            <div class="day-date">{{ \Carbon\Carbon::parse($day->date)->format('m/d') }}</div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection

@section('styles')
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    .stat-icon {
        font-size: 36px;
        margin-bottom: 10px;
    }

    .stat-number {
        font-size: 32px;
        font-weight: bold;
        color: #007bff;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
    }

    .status-pending {
        border-color: #ffc107;
    }
    .status-pending .stat-number {
        color: #ffc107;
    }

    .status-contacted {
        border-color: #28a745;
    }
    .status-contacted .stat-number {
        color: #28a745;
    }

    .status-qualified {
        border-color: #17a2b8;
    }
    .status-qualified .stat-number {
        color: #17a2b8;
    }

    .status-invalid {
        border-color: #dc3545;
    }
    .status-invalid .stat-number {
        color: #dc3545;
    }

    .region-stats {
        margin-top: 20px;
    }

    .region-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        gap: 15px;
    }

    .region-name {
        min-width: 80px;
        font-weight: bold;
    }

    .region-bar {
        flex: 1;
        height: 20px;
        background-color: #e9ecef;
        border-radius: 10px;
        overflow: hidden;
    }

    .region-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 10px;
        transition: width 0.3s;
    }

    .region-count {
        min-width: 40px;
        text-align: right;
        font-weight: bold;
        color: #007bff;
    }

    .daily-chart {
        display: flex;
        align-items: end;
        justify-content: space-between;
        height: 200px;
        margin-top: 20px;
        padding: 20px 0;
        border-bottom: 1px solid #ddd;
    }

    .day-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        margin: 0 2px;
    }

    .day-bar {
        background: linear-gradient(0deg, #667eea, #764ba2);
        width: 20px;
        min-height: 5px;
        border-radius: 2px 2px 0 0;
        margin-bottom: 5px;
    }

    .day-count {
        font-size: 12px;
        font-weight: bold;
        color: #007bff;
        margin-bottom: 5px;
    }

    .day-date {
        font-size: 10px;
        color: #666;
        transform: rotate(-45deg);
        white-space: nowrap;
    }
</style>
@endsection