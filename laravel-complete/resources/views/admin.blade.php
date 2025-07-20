@extends('layouts.app')

@section('title', '管理後台 - 貸款案件管理系統')

@section('content')
<div class="header">
    <h1><i class="fas fa-tachometer-alt"></i> 貸款案件管理系統</h1>
    <p>LINE Bot CRM 系統 - 客戶資料管理</p>
</div>

<div class="card">
    <h3>統計資訊</h3>
    <div class="stats-section">
        <div class="stat-item">
            <div class="stat-number" id="totalSubmissions">-</div>
            <div class="stat-label">總客戶數</div>
        </div>
        <div class="stat-item">
            <div class="stat-number" id="todaySubmissions">-</div>
            <div class="stat-label">今日新增</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="controls-section">
        <button class="btn btn-primary" onclick="openModal('addModal')">
            <i class="fas fa-plus"></i> 新增案件
        </button>
        <button class="btn btn-success" onclick="refreshData()">
            <i class="fas fa-sync-alt"></i> 重新整理
        </button>
    </div>

    <div class="filters-section">
        <div class="filter-row">
            <input type="text" id="searchInput" class="form-control" placeholder="搜尋客戶姓名或電話..." onkeyup="applyFilters()">
            
            <select id="regionFilter" class="form-control" onchange="applyFilters()">
                <option value="">所有區域</option>
                <option value="台北市">台北市</option>
                <option value="新北市">新北市</option>
                <option value="桃園市">桃園市</option>
                <option value="台中市">台中市</option>
                <option value="台南市">台南市</option>
                <option value="高雄市">高雄市</option>
            </select>

            <select id="statusFilter" class="form-control" onchange="applyFilters()">
                <option value="">所有狀態</option>
                <option value="pending">待處理</option>
                <option value="contacted">已聯絡</option>
                <option value="qualified">已認證</option>
                <option value="invalid">無效</option>
            </select>
        </div>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>提交時間</th>
                    <th>客戶姓名</th>
                    <th>電話</th>
                    <th>LINE ID</th>
                    <th>區域</th>
                    <th>狀態</th>
                    <th>負責人</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="submissionsBody">
                <!-- 資料將透過 JavaScript 載入 -->
            </tbody>
        </table>
    </div>
</div>

<!-- 新增/編輯案件模態框 -->
<div id="addModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3 id="addModalTitle">新增案件</h3>
        <form id="submissionForm">
            <div class="form-group">
                <label class="form-label">客戶姓名 *</label>
                <input type="text" id="customerName" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">電話 *</label>
                <input type="tel" id="phone" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">LINE ID</label>
                <input type="text" id="lineId" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">區域 *</label>
                <select id="region" class="form-control" required>
                    <option value="">請選擇區域</option>
                    <option value="台北市">台北市</option>
                    <option value="新北市">新北市</option>
                    <option value="桃園市">桃園市</option>
                    <option value="台中市">台中市</option>
                    <option value="台南市">台南市</option>
                    <option value="高雄市">高雄市</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">狀態</label>
                <select id="status" class="form-control">
                    <option value="pending">待處理</option>
                    <option value="contacted">已聯絡</option>
                    <option value="qualified">已認證</option>
                    <option value="invalid">無效</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-success" onclick="saveSubmission()">儲存</button>
                <button type="button" class="btn btn-danger" onclick="closeModal('addModal')">取消</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
    .stats-section {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .stat-item {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        flex: 1;
        min-width: 120px;
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

    .controls-section {
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .filters-section {
        margin-bottom: 20px;
    }

    .filter-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .filter-row > * {
        flex: 1;
        min-width: 200px;
    }

    .table-container {
        overflow-x: auto;
    }

    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .action-btn {
        padding: 4px 8px;
        font-size: 12px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        margin-right: 5px;
    }

    .action-btn.edit {
        background-color: #ffc107;
        color: #212529;
    }

    .action-btn.delete {
        background-color: #dc3545;
        color: white;
    }

    @media (max-width: 768px) {
        .stats-section {
            flex-direction: column;
        }
        
        .controls-section {
            flex-direction: column;
        }
        
        .filter-row {
            flex-direction: column;
        }
        
        .filter-row > * {
            min-width: auto;
        }
    }
</style>
@endsection

@section('scripts')
<script>
    let submissions = [];
    let currentEditingSubmission = null;

    // 頁面載入時獲取資料
    document.addEventListener('DOMContentLoaded', function() {
        loadSubmissions();
        loadStats();
    });

    // 載入統計資料
    async function loadStats() {
        try {
            const response = await fetch('/api/stats');
            const data = await response.json();
            
            document.getElementById('totalSubmissions').textContent = data.total;
            document.getElementById('todaySubmissions').textContent = data.today;
        } catch (error) {
            console.error('載入統計資料錯誤:', error);
        }
    }

    // 載入提交記錄
    async function loadSubmissions() {
        try {
            // 這裡需要實作獲取提交記錄的 API
            // 暫時使用空陣列
            renderSubmissions(submissions);
        } catch (error) {
            console.error('載入提交記錄錯誤:', error);
        }
    }

    // 渲染提交記錄表格
    function renderSubmissions(data) {
        const tbody = document.getElementById('submissionsBody');
        tbody.innerHTML = '';

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">暫無資料</td></tr>';
            return;
        }

        data.forEach((sub, index) => {
            const row = document.createElement('tr');
            const submittedAt = new Date(sub.submitted_at).toLocaleString('zh-TW');
            
            row.innerHTML = `
                <td>${submittedAt}</td>
                <td>${sub.customer_name}</td>
                <td>${sub.phone}</td>
                <td>${sub.line_id || '-'}</td>
                <td>${sub.region}</td>
                <td>${getStatusLabel(sub.status)}</td>
                <td>${sub.assigned_to || '-'}</td>
                <td>
                    <button class="action-btn edit" onclick="editSubmission(${sub.id})">編輯</button>
                    <button class="action-btn delete" onclick="deleteSubmission(${sub.id})">刪除</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // 獲取狀態標籤
    function getStatusLabel(status) {
        const labels = {
            'pending': '待處理',
            'contacted': '已聯絡',
            'qualified': '已認證',
            'invalid': '無效',
            'duplicate': '重複'
        };
        return labels[status] || status;
    }

    // 篩選功能
    function applyFilters() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const regionFilter = document.getElementById('regionFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;

        const filtered = submissions.filter(sub => {
            const matchesSearch = !searchTerm || 
                sub.customer_name.toLowerCase().includes(searchTerm) ||
                sub.phone.includes(searchTerm);
            const matchesRegion = !regionFilter || sub.region === regionFilter;
            const matchesStatus = !statusFilter || sub.status === statusFilter;
            
            return matchesSearch && matchesRegion && matchesStatus;
        });

        renderSubmissions(filtered);
    }

    // 開啟模態框
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'flex';
    }

    // 關閉模態框
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        if (modalId === 'addModal') {
            document.getElementById('submissionForm').reset();
            currentEditingSubmission = null;
            document.getElementById('addModalTitle').textContent = '新增案件';
        }
    }

    // 編輯提交記錄
    function editSubmission(id) {
        currentEditingSubmission = submissions.find(s => s.id === id);
        if (currentEditingSubmission) {
            document.getElementById('addModalTitle').textContent = '編輯案件';
            document.getElementById('customerName').value = currentEditingSubmission.customer_name;
            document.getElementById('phone').value = currentEditingSubmission.phone;
            document.getElementById('lineId').value = currentEditingSubmission.line_id || '';
            document.getElementById('region').value = currentEditingSubmission.region;
            document.getElementById('status').value = currentEditingSubmission.status;
            openModal('addModal');
        }
    }

    // 儲存提交記錄
    async function saveSubmission() {
        const formData = {
            customer_name: document.getElementById('customerName').value,
            phone: document.getElementById('phone').value,
            line_id: document.getElementById('lineId').value,
            region: document.getElementById('region').value,
            status: document.getElementById('status').value
        };

        if (!formData.customer_name || !formData.phone || !formData.region) {
            alert('請填寫必填欄位');
            return;
        }

        try {
            // 這裡需要實作儲存 API
            console.log('儲存資料:', formData);
            closeModal('addModal');
            loadSubmissions();
            loadStats();
        } catch (error) {
            console.error('儲存錯誤:', error);
            alert('儲存失敗');
        }
    }

    // 刪除提交記錄
    async function deleteSubmission(id) {
        if (!confirm('確定要刪除這筆記錄嗎？')) {
            return;
        }

        try {
            // 這裡需要實作刪除 API
            console.log('刪除記錄:', id);
            loadSubmissions();
            loadStats();
        } catch (error) {
            console.error('刪除錯誤:', error);
            alert('刪除失敗');
        }
    }

    // 重新整理資料
    function refreshData() {
        loadSubmissions();
        loadStats();
    }

    // 點擊模態框背景關閉
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
</script>
@endsection