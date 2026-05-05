<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\CashFlowLog;
use App\Models\UserLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function user(Request $request)
    {
        $q = UserLog::with(['user:id,name,email', 'actor:id,name']);
        if ($action = $request->input('action')) $q->where('action', $action);
        if ($userId = $request->input('user_id')) $q->where('user_id', $userId);
        return response()->json($q->orderBy('logged_at', 'desc')->paginate(30));
    }

    public function attendance(Request $request)
    {
        $q = AttendanceLog::with(['user:id,name,email', 'actor:id,name']);
        if ($action = $request->input('action')) $q->where('action', $action);
        if ($userId = $request->input('user_id')) $q->where('user_id', $userId);
        return response()->json($q->orderBy('logged_at', 'desc')->paginate(30));
    }

    public function cashFlow(Request $request)
    {
        $q = CashFlowLog::with(['actor:id,name', 'cashFlow:id,description,amount,type']);
        if ($action = $request->input('action')) $q->where('action', $action);
        return response()->json($q->orderBy('logged_at', 'desc')->paginate(30));
    }
}
