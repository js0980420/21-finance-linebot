<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'role' => 'required|in:admin,sales,manager',
            'username' => 'required|string'
        ]);

        // 模擬登入驗證
        $users = [
            'admin' => ['username' => 'admin', 'name' => '系統管理員', 'permissions' => ['view_all', 'edit_all', 'delete_all']],
            'manager' => ['username' => 'manager', 'name' => '業務主管', 'permissions' => ['view_all', 'edit_sales']],
            'sales01' => ['username' => 'sales01', 'name' => '張業務', 'permissions' => ['view_own'], 'sales_code' => 'S001'],
            'sales02' => ['username' => 'sales02', 'name' => '李業務', 'permissions' => ['view_own'], 'sales_code' => 'S002'],
            'dealer01' => ['username' => 'dealer01', 'name' => '台北經銷商', 'permissions' => ['view_dealer'], 'dealer_code' => 'D001'],
            'dealer02' => ['username' => 'dealer02', 'name' => '台中經銷商', 'permissions' => ['view_dealer'], 'dealer_code' => 'D002'],
        ];

        $username = $request->username;
        $role = $request->role;

        if (isset($users[$username])) {
            $user = $users[$username];
            
            Session::put('user', [
                'username' => $username,
                'name' => $user['name'],
                'role' => $role,
                'permissions' => $user['permissions'],
                'sales_code' => $user['sales_code'] ?? null,
                'dealer_code' => $user['dealer_code'] ?? null,
            ]);

            return redirect()->route('dashboard')->with('success', "歡迎 {$user['name']}！");
        }

        return back()->withErrors(['login' => '登入失敗，請檢查帳號'])->withInput();
    }

    public function logout()
    {
        Session::forget('user');
        return redirect()->route('login')->with('success', '已成功登出');
    }
}