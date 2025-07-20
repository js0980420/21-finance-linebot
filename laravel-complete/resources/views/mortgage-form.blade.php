@extends('layouts.app')

@section('title', '房貸先生 - 免費貸款評估')

@section('content')
<div style="min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); padding: 40px; max-width: 500px; width: 100%; animation: slideUp 0.6s ease-out;">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 48px; color: #667eea; margin-bottom: 10px;">🏠</div>
            <h1 style="color: #333; font-size: 28px; margin-bottom: 5px;">房貸先生</h1>
            <p style="color: #666; font-size: 16px;">免費貸款評估 · 快速審核</p>
        </div>

        <form id="mortgageForm">
            @csrf
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user" style="color: #667eea; margin-right: 8px;"></i>
                    姓名 *
                </label>
                <input type="text" id="name" name="name" class="form-control" placeholder="請輸入您的姓名" required>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-phone" style="color: #667eea; margin-right: 8px;"></i>
                    手機號碼 *
                </label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="0912345678" pattern="09[0-9]{8}" required>
                <small style="color: #666; font-size: 12px;">請輸入有效的台灣手機號碼</small>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fab fa-line" style="color: #667eea; margin-right: 8px;"></i>
                    LINE ID
                </label>
                <input type="text" id="lineId" name="lineId" class="form-control" placeholder="您的 LINE ID (選填)">
                <small style="color: #666; font-size: 12px;">提供 LINE ID 可獲得更快的服務回應</small>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-map-marker-alt" style="color: #667eea; margin-right: 8px;"></i>
                    所在區域 *
                </label>
                <select id="area" name="area" class="form-control" required>
                    <option value="">請選擇您的所在區域</option>
                    <option value="台北市">台北市</option>
                    <option value="新北市">新北市</option>
                    <option value="桃園市">桃園市</option>
                    <option value="台中市">台中市</option>
                    <option value="台南市">台南市</option>
                    <option value="高雄市">高雄市</option>
                    <option value="基隆市">基隆市</option>
                    <option value="新竹市">新竹市</option>
                    <option value="嘉義市">嘉義市</option>
                    <option value="新竹縣">新竹縣</option>
                    <option value="苗栗縣">苗栗縣</option>
                    <option value="彰化縣">彰化縣</option>
                    <option value="南投縣">南投縣</option>
                    <option value="雲林縣">雲林縣</option>
                    <option value="嘉義縣">嘉義縣</option>
                    <option value="屏東縣">屏東縣</option>
                    <option value="宜蘭縣">宜蘭縣</option>
                    <option value="花蓮縣">花蓮縣</option>
                    <option value="台東縣">台東縣</option>
                    <option value="澎湖縣">澎湖縣</option>
                    <option value="金門縣">金門縣</option>
                    <option value="連江縣">連江縣</option>
                </select>
            </div>

            <div id="messageArea" style="margin-bottom: 20px;"></div>

            <button type="submit" class="btn btn-primary" id="submitBtn" style="width: 100%; padding: 15px; font-size: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 10px;">
                <i class="fas fa-paper-plane" style="margin-right: 8px;"></i>
                <span id="submitText">提交貸款申請</span>
            </button>
        </form>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <div style="color: #666; margin-bottom: 15px;">
                <i class="fas fa-shield-alt" style="color: #28a745; margin-right: 5px;"></i>
                您的資料將受到嚴格保護
            </div>
            
            <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; color: #666; font-size: 14px;">
                    <i class="fas fa-clock" style="color: #667eea; margin-right: 5px;"></i>
                    24小時內回覆
                </div>
                <div style="display: flex; align-items: center; color: #666; font-size: 14px;">
                    <i class="fas fa-calculator" style="color: #667eea; margin-right: 5px;"></i>
                    免費評估
                </div>
                <div style="display: flex; align-items: center; color: #666; font-size: 14px;">
                    <i class="fas fa-handshake" style="color: #667eea; margin-right: 5px;"></i>
                    專業服務
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .loading {
        opacity: 0.7;
        pointer-events: none;
    }

    .success-message {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        margin-bottom: 20px;
        animation: slideUp 0.3s ease-out;
    }

    .error-message {
        background: linear-gradient(135deg, #dc3545 0%, #e91e63 100%);
        color: white;
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        margin-bottom: 20px;
        animation: slideUp 0.3s ease-out;
    }

    @media (max-width: 480px) {
        .container > div {
            padding: 30px 20px;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .btn-primary {
            padding: 12px;
            font-size: 14px;
        }
    }
</style>
@endsection

@section('scripts')
<script>
    document.getElementById('mortgageForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const messageArea = document.getElementById('messageArea');
        
        // 清除之前的消息
        messageArea.innerHTML = '';
        
        // 設置載入狀態
        submitBtn.classList.add('loading');
        submitText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';
        
        const formData = {
            name: document.getElementById('name').value,
            phone: document.getElementById('phone').value,
            lineId: document.getElementById('lineId').value,
            area: document.getElementById('area').value
        };
        
        try {
            const response = await fetch('/api/mortgage-form', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                messageArea.innerHTML = `
                    <div class="success-message">
                        <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                        ${result.message}
                    </div>
                `;
                
                // 清空表單
                document.getElementById('mortgageForm').reset();
                
                // 3秒後重置按鈕
                setTimeout(() => {
                    submitText.innerHTML = '<i class="fas fa-check"></i> 提交成功';
                }, 1000);
                
            } else {
                throw new Error(result.message || '提交失敗');
            }
            
        } catch (error) {
            console.error('提交錯誤:', error);
            
            messageArea.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                    ${error.message || '系統暫時忙碌，請稍後再試'}
                </div>
            `;
        } finally {
            // 重置按鈕狀態
            setTimeout(() => {
                submitBtn.classList.remove('loading');
                submitText.innerHTML = '<i class="fas fa-paper-plane" style="margin-right: 8px;"></i>提交貸款申請';
            }, 2000);
        }
    });

    // 手機號碼格式化
    document.getElementById('phone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 10) {
            value = value.substring(0, 10);
        }
        e.target.value = value;
    });

    // 表單驗證
    document.getElementById('phone').addEventListener('blur', function(e) {
        const phone = e.target.value;
        const phonePattern = /^09\d{8}$/;
        
        if (phone && !phonePattern.test(phone)) {
            e.target.style.borderColor = '#dc3545';
            e.target.style.boxShadow = '0 0 0 2px rgba(220, 53, 69, 0.25)';
        } else {
            e.target.style.borderColor = '#ddd';
            e.target.style.boxShadow = 'none';
        }
    });
</script>
@endsection