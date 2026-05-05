<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashCategory;
use Illuminate\Http\Request;

class CashCategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = CashCategory::query();
        if ($type = $request->input('type')) $q->where('type', $type);
        return response()->json($q->orderBy('type')->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'type'  => 'required|in:income,expense',
            'icon'  => 'nullable|string|max:50',
            'color' => 'nullable|string|max:9',
        ]);
        $cat = CashCategory::create($data);
        return response()->json($cat, 201);
    }

    public function update(Request $request, CashCategory $cashCategory)
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:100',
            'icon'      => 'sometimes|nullable|string|max:50',
            'color'     => 'sometimes|nullable|string|max:9',
            'is_active' => 'sometimes|boolean',
        ]);
        $cashCategory->update($data);
        return response()->json($cashCategory);
    }

    public function destroy(CashCategory $cashCategory)
    {
        $cashCategory->delete();
        return response()->json(['message' => 'Dihapus']);
    }
}
