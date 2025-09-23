<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\CustomerCase;
use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Models\CustomField;
use App\Models\CustomFieldValue;

class CaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // POST /api/customers/{customer}/cases
    public function storeForCustomer(Request $request, Customer $customer)
    {
        $validator = Validator::make($request->all(), [
            'loan_amount' => 'required|numeric|min:0',
            'lead_id' => 'sometimes|nullable|exists:customer_leads,id',
            'loan_type' => 'sometimes|nullable|string|max:50',
            'loan_term' => 'sometimes|nullable|integer|min:0',
            'interest_rate' => 'sometimes|nullable|numeric|min:0',
            'notes' => 'sometimes|nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $case = CustomerCase::create([
            'customer_id' => $customer->id,
            'lead_id' => $data['lead_id'] ?? null,
            'case_number' => CustomerCase::generateCaseNumber(),
            'loan_amount' => $data['loan_amount'],
            'loan_type' => $data['loan_type'] ?? null,
            'loan_term' => $data['loan_term'] ?? null,
            'interest_rate' => $data['interest_rate'] ?? null,
            'status' => 'unassigned',
            'submitted_at' => now(),
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        // 更新 customer 快照
        $customer->case_status = 'submitted';
        $customer->save();

        // 活動紀錄
        CustomerActivity::create([
            'customer_id' => $customer->id,
            'user_id' => Auth::id(),
            'activity_type' => CustomerActivity::TYPE_CASE_SUBMITTED,
            'description' => '案件送件建立',
            'new_data' => ['case_id' => $case->id, 'case_number' => $case->case_number],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['message' => 'created', 'case' => $case->load('customer')], 201);
    }

    // GET /api/cases
    public function index(Request $request)
    {
        $query = CustomerCase::with(['customer']);

        if ($request->has('status')) {
            $status = $request->get('status');
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        if ($request->has('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('case_number', 'like', "%$s%")
                  ->orWhereHas('customer', function ($qq) use ($s) {
                      $qq->where('name', 'like', "%$s%")
                         ->orWhere('phone', 'like', "%$s%")
                         ->orWhere('email', 'like', "%$s%");
                  });
            });
        }

        // date range (by created_at by default)
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // amount range filter
        if ($request->has('min_amount')) {
            $query->where('loan_amount', '>=', $request->get('min_amount'));
        }
        if ($request->has('max_amount')) {
            $query->where('loan_amount', '<=', $request->get('max_amount'));
        }

        // loan type filter
        if ($request->has('loan_type')) {
            $query->where('loan_type', 'like', '%'.$request->get('loan_type').'%');
        }

        $perPage = (int)($request->get('per_page', 15));
        $cases = $query->orderByDesc('created_at')->paginate($perPage);
        return response()->json($cases);
    }

    // GET /api/cases/{case}
    public function show(CustomerCase $case)
    {
        return response()->json(['case' => $case->load('customer')]);
    }

    // PUT /api/cases/{case}
    public function update(Request $request, CustomerCase $case)
    {
        $validator = Validator::make($request->all(), [
            'loan_amount' => 'sometimes|numeric|min:0',
            'loan_type' => 'sometimes|nullable|string|max:50',
            'loan_term' => 'sometimes|nullable|integer|min:0',
            'interest_rate' => 'sometimes|nullable|numeric|min:0',
            'status' => 'sometimes|in:unassigned,valid_customer,invalid_customer,customer_service,blacklist,approved_disbursed,approved_undisbursed,conditional_approval,rejected,tracking_management',
            'approved_amount' => 'sometimes|nullable|numeric|min:0',
            'disbursed_amount' => 'sometimes|nullable|numeric|min:0',
            'rejection_reason' => 'sometimes|nullable|string',
            'notes' => 'sometimes|nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $old = $case->toArray();
        $data = $validator->validated();

        $statusChanged = array_key_exists('status', $data) && $data['status'] !== $case->status;
        
        // 狀態轉換規範：10種狀態的流轉規則
        if ($statusChanged) {
            $current = $case->status;
            $new = $data['status'];
            $validTransitions = [
                'unassigned' => ['valid_customer', 'invalid_customer', 'customer_service', 'blacklist'],
                'valid_customer' => ['approved_disbursed', 'approved_undisbursed', 'conditional_approval', 'rejected', 'tracking_management'],
                'invalid_customer' => ['valid_customer'], // 可以重新評估為有效客
                'customer_service' => ['valid_customer', 'invalid_customer', 'blacklist'],
                'blacklist' => [], // 黑名單後不可再變更
                'approved_disbursed' => [], // 已撥款不可再變更
                'approved_undisbursed' => ['approved_disbursed'], // 可轉為已撥款
                'conditional_approval' => ['approved_disbursed', 'approved_undisbursed', 'rejected'],
                'rejected' => [], // 婉拒後不可再變更
                'tracking_management' => ['valid_customer', 'approved_disbursed', 'approved_undisbursed', 'conditional_approval', 'rejected'],
            ];

            if (!isset($validTransitions[$current]) || !in_array($new, $validTransitions[$current])) {
                return response()->json([
                    'message' => "不允許的狀態轉換：{$current} → {$new}",
                    'valid_transitions' => $validTransitions[$current] ?? [],
                    'status_labels' => CustomerCase::getStatusLabels()
                ], 422);
            }
        }
        if ($statusChanged) {
            // 更新狀態相關時間戳和指派資訊
            $data['status_updated_at'] = now();
            $data['status_updated_by'] = Auth::id();

            switch ($data['status']) {
                case 'valid_customer':
                case 'invalid_customer':
                case 'customer_service':
                case 'blacklist':
                case 'tracking_management':
                    // 這些狀態需要指派業務
                    if (!$case->assigned_to && !isset($data['assigned_to'])) {
                        $data['assigned_to'] = Auth::id();
                        $data['assigned_at'] = now();
                    }
                    break;
                case 'approved_disbursed':
                    $data['approved_at'] = now();
                    $data['disbursed_at'] = now();
                    break;
                case 'approved_undisbursed':
                    $data['approved_at'] = now();
                    break;
                case 'conditional_approval':
                    $data['approved_at'] = now();
                    break;
                case 'rejected':
                    $data['rejected_at'] = now();
                    break;
            }
        }

        $case->fill($data)->save();

        // 同步更新 Customer 快照
        $customer = $case->customer;
        if ($statusChanged) {
            $customer->case_status = $case->status;
        }
        if (array_key_exists('approved_amount', $data)) {
            $customer->approved_amount = $data['approved_amount'];
        }
        if (array_key_exists('disbursed_amount', $data)) {
            $customer->disbursed_amount = $data['disbursed_amount'];
        }
        $customer->save();

        // 活動紀錄
        if ($statusChanged) {
            $statusLabels = CustomerCase::getStatusLabels();
            $oldLabel = $statusLabels[$old['status'] ?? ''] ?? $old['status'] ?? 'unknown';
            $newLabel = $statusLabels[$case->status] ?? $case->status;

            $typeMap = [
                'unassigned' => CustomerActivity::TYPE_CASE_SUBMITTED,
                'valid_customer' => CustomerActivity::TYPE_UPDATED,
                'invalid_customer' => CustomerActivity::TYPE_UPDATED,
                'customer_service' => CustomerActivity::TYPE_UPDATED,
                'blacklist' => CustomerActivity::TYPE_UPDATED,
                'approved_disbursed' => CustomerActivity::TYPE_DISBURSED,
                'approved_undisbursed' => CustomerActivity::TYPE_CASE_APPROVED,
                'conditional_approval' => CustomerActivity::TYPE_CASE_APPROVED,
                'rejected' => CustomerActivity::TYPE_CASE_REJECTED,
                'tracking_management' => CustomerActivity::TYPE_UPDATED,
            ];

            CustomerActivity::create([
                'customer_id' => $case->customer_id,
                'user_id' => Auth::id(),
                'activity_type' => $typeMap[$case->status] ?? CustomerActivity::TYPE_UPDATED,
                'description' => "案件狀態變更：{$oldLabel} → {$newLabel}",
                'old_data' => ['status' => $old['status'] ?? null],
                'new_data' => ['status' => $case->status],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return response()->json(['message' => 'updated', 'case' => $case->load('customer')]);
    }

    // POST /api/cases - 直接創建案件（不依賴客戶）
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'consultation_item' => 'nullable|string|max:255',
            'demand_amount' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:unassigned,valid_customer,invalid_customer,customer_service,blacklist,approved_disbursed,approved_undisbursed,conditional_approval,rejected,tracking_management',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'channel' => 'nullable|string|max:255',
            'website_source' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['case_number'] = CustomerCase::generateCaseNumber();
        $data['status'] = $data['status'] ?? 'unassigned';
        $data['created_by'] = Auth::id();

        if (!empty($data['assigned_to'])) {
            $data['assigned_at'] = now();
        }

        $case = CustomerCase::create($data);

        return response()->json(['message' => 'created', 'case' => $case->load(['assignedUser', 'statusUpdater'])], 201);
    }

    // PUT /api/cases/{case}/assign - 指派案件
    public function assign(Request $request, CustomerCase $case)
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
            'status_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $case->update([
            'assigned_to' => $data['assigned_to'],
            'assigned_at' => now(),
            'status_note' => $data['status_note'] ?? null,
            'status_updated_at' => now(),
            'status_updated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'assigned', 'case' => $case->load(['assignedUser', 'statusUpdater'])]);
    }

    // DELETE /api/cases/{case}
    public function destroy(CustomerCase $case)
    {
        $case->delete();
        return response()->json(['message' => 'deleted']);
    }

    // GET /api/cases/status-summary - 各狀態案件數量統計
    public function statusSummary()
    {
        $statusCounts = CustomerCase::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $labels = CustomerCase::getStatusLabels();
        $groups = CustomerCase::getStatusGroups();

        $result = [];
        foreach ($groups as $groupName => $statuses) {
            $result[$groupName] = [];
            foreach ($statuses as $status => $label) {
                $result[$groupName][$status] = [
                    'label' => $label,
                    'count' => $statusCounts[$status] ?? 0
                ];
            }
        }

        return response()->json($result);
    }
}
