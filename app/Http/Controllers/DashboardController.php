<?php

namespace App\Http\Controllers;

use App\Models\MortgageFormSubmission;
use App\Models\SalesLineAccount;
use App\Models\LineQueryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Session::get('user');
        if (!$user) {
            return redirect()->route('login');
        }

        return view('dashboard.index', compact('user'));
    }

    public function submissions(Request $request)
    {
        $user = Session::get('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $query = MortgageFormSubmission::query();

        // 根據用戶權限過濾資料
        if (!in_array('view_all', $user['permissions'])) {
            if (in_array('view_own', $user['permissions']) && $user['sales_code']) {
                $query->where('assigned_to', $user['sales_code']);
            } elseif (in_array('view_dealer', $user['permissions']) && $user['dealer_code']) {
                // 經銷商只能看自己的區域
                $dealerRegions = $this->getDealerRegions($user['dealer_code']);
                $query->whereIn('region', $dealerRegions);
            }
        }

        // 搜尋功能
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('line_id', 'LIKE', "%{$search}%");
            });
        }

        // 狀態篩選
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // 區域篩選
        if ($request->region) {
            $query->where('region', $request->region);
        }

        // 日期範圍篩選
        if ($request->date_from) {
            $query->whereDate('submitted_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('submitted_at', '<=', $request->date_to);
        }

        $submissions = $query->orderBy('submitted_at', 'desc')->paginate(20);

        $regions = DB::table('mortgage_form_submissions')
                    ->select('region')
                    ->distinct()
                    ->orderBy('region')
                    ->pluck('region');

        return view('dashboard.submissions', compact('user', 'submissions', 'regions'));
    }

    public function salesAccounts()
    {
        $user = Session::get('user');
        if (!$user || !in_array('view_all', $user['permissions'])) {
            return redirect()->route('dashboard')->with('error', '權限不足');
        }

        $salesAccounts = SalesLineAccount::orderBy('sales_code')->get();
        return view('dashboard.sales-accounts', compact('user', 'salesAccounts'));
    }

    public function queryLogs(Request $request)
    {
        $user = Session::get('user');
        if (!$user || !in_array('view_all', $user['permissions'])) {
            return redirect()->route('dashboard')->with('error', '權限不足');
        }

        $query = LineQueryLog::query();

        if ($request->line_user_id) {
            $query->where('line_user_id', 'LIKE', "%{$request->line_user_id}%");
        }

        if ($request->query_type) {
            $query->where('query_type', $request->query_type);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('dashboard.query-logs', compact('user', 'logs'));
    }

    public function statistics()
    {
        $user = Session::get('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $baseQuery = MortgageFormSubmission::query();

        // 權限過濾
        if (!in_array('view_all', $user['permissions'])) {
            if (in_array('view_own', $user['permissions']) && $user['sales_code']) {
                $baseQuery->where('assigned_to', $user['sales_code']);
            } elseif (in_array('view_dealer', $user['permissions']) && $user['dealer_code']) {
                $dealerRegions = $this->getDealerRegions($user['dealer_code']);
                $baseQuery->whereIn('region', $dealerRegions);
            }
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'today' => (clone $baseQuery)->whereDate('submitted_at', today())->count(),
            'this_week' => (clone $baseQuery)->whereBetween('submitted_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => (clone $baseQuery)->whereMonth('submitted_at', now()->month)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'contacted' => (clone $baseQuery)->where('status', 'contacted')->count(),
            'qualified' => (clone $baseQuery)->where('status', 'qualified')->count(),
            'invalid' => (clone $baseQuery)->where('status', 'invalid')->count(),
        ];

        // 區域統計
        $regionStats = (clone $baseQuery)
            ->select('region', DB::raw('count(*) as count'))
            ->groupBy('region')
            ->orderBy('count', 'desc')
            ->get();

        // 每日統計（最近30天）
        $dailyStats = (clone $baseQuery)
            ->select(DB::raw('DATE(submitted_at) as date'), DB::raw('count(*) as count'))
            ->where('submitted_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('dashboard.statistics', compact('user', 'stats', 'regionStats', 'dailyStats'));
    }

    private function getDealerRegions($dealerCode)
    {
        // 模擬經銷商負責區域
        $dealerRegions = [
            'D001' => ['台北市', '新北市', '基隆市'],
            'D002' => ['台中市', '彰化縣', '南投縣'],
            'D003' => ['高雄市', '台南市', '屏東縣'],
        ];

        return $dealerRegions[$dealerCode] ?? [];
    }
}