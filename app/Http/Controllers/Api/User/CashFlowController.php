<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\CashFlow;
use Illuminate\Http\Request;

class CashFlowController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $rows = CashFlow::with('category:id,name,icon,color,type')
            ->where('created_by', $user->id)
            ->orderBy('date', 'desc')
            ->paginate(20);
        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id'  => 'nullable|exists:cash_categories,id',
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0',
            'type'         => 'required|in:income,expense',
            'date'         => 'required|date',
            'reference_no' => 'nullable|string|max:100',
        ]);
        $data['created_by'] = $request->user()->id;
        $data['status']     = 'pending'; // user butuh approval admin

        $cf = CashFlow::create($data);
        return response()->json(['data' => $cf->load('category')], 201);
    }
}
