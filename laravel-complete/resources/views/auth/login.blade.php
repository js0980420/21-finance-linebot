@extends('layouts.app')

@section('title', '系統登入')

@section('content')
<div style="min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: white; border-radius: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); padding: 40px; max-width: 450px; width: 100%;">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 48px; color: #667eea; margin-bottom: 10px;">🏢</div>
            <h1 style="color: #333; font-size: 24px; margin-bottom: 5px;">貸款管理系統</h1>
            <p style="color: #666; font-size: 14px;">案件管理後台</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user-tag" style="color: #667eea; margin-right: 8px;"></i>
                    選擇身份
                </label>
                <select name="role" class="form-control" required>
                    <option value="">請選擇身份</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>系統管理員</option>
                    <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>業務主管</option>
                    <option value="sales" {{ old('role') == 'sales' ? 'selected' : '' }}>業務員</option>
                    <option value="dealer" {{ old('role') == 'dealer' ? 'selected' : '' }}>經銷商</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user" style="color: #667eea; margin-right: 8px;"></i>
                    帳號
                </label>
                <select name="username" class="form-control" required>
                    <option value="">請選擇帳號</option>
                    <optgroup label="系統管理員">
                        <option value="admin" {{ old('username') == 'admin' ? 'selected' : '' }}>admin - 系統管理員</option>
                    </optgroup>
                    <optgroup label="業務主管">
                        <option value="manager" {{ old('username') == 'manager' ? 'selected' : '' }}>manager - 業務主管</option>
                    </optgroup>
                    <optgroup label="業務員">
                        <option value="sales01" {{ old('username') == 'sales01' ? 'selected' : '' }}>sales01 - 張業務</option>
                        <option value="sales02" {{ old('username') == 'sales02' ? 'selected' : '' }}>sales02 - 李業務</option>
                    </optgroup>
                    <optgroup label="經銷商">
                        <option value="dealer01" {{ old('username') == 'dealer01' ? 'selected' : '' }}>dealer01 - 台北經銷商</option>
                        <option value="dealer02" {{ old('username') == 'dealer02' ? 'selected' : '' }}>dealer02 - 台中經銷商</option>
                    </optgroup>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 8px; margin-top: 10px;">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
                登入系統
            </button>
        </form>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
            <h4 style="color: #333; margin-bottom: 15px;">測試帳號說明</h4>
            
            <div style="text-align: left; font-size: 14px; color: #666;">
                <div style="margin-bottom: 10px;"><strong>系統管理員：</strong>可查看所有資料</div>
                <div style="margin-bottom: 10px;"><strong>業務主管：</strong>可查看所有業務資料</div>
                <div style="margin-bottom: 10px;"><strong>業務員：</strong>只能查看自己的客戶</div>
                <div style="margin-bottom: 10px;"><strong>經銷商：</strong>只能查看負責區域</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    optgroup {
        font-weight: bold;
        color: #333;
    }

    option {
        font-weight: normal;
        color: #666;
    }
</style>
@endsection