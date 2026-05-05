<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $rows = Setting::orderBy('group')->orderBy('key')->get();
        $grouped = $rows->groupBy('group');
        return response()->json($grouped);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings'           => 'required|array',
            'settings.*.key'     => 'required|string',
            'settings.*.value'   => 'nullable',
        ]);
        foreach ($data['settings'] as $s) {
            Setting::set($s['key'], $s['value']);
        }
        return response()->json(['message' => 'Settings updated']);
    }
}
