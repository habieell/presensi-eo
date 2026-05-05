<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashFlow;
use App\Services\ActivityLog\CsvExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashFlowController extends Controller
{
    public function index(Request $request)
    {
        $q = CashFlow::with(['creator:id,name,email', 'category:id,name,icon,color,type', 'approver:id,name']);

        if ($type = $request->input('type'))     $q->where('type', $type);
        if ($cat = $request->input('category_id'))  $q->where('category_id', $cat);
        if ($status = $request->input('status'))   $q->where('status', $status);
        if ($month = $request->input('month'))    $q->whereMonth('date', $month);
        if ($year = $request->input('year'))     $q->whereYear('date', $year);
        if ($search = $request->input('q'))       $q->where('description', 'like', "%$search%");

        $rows = $q->orderBy('date', 'desc')->orderBy('id', 'desc')->paginate(20);

        $approved = CashFlow::approved();
        $totalIncome  = (clone $approved)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $approved)->where('type', 'expense')->sum('amount');

        return response()->json([
            'data' => $rows,
            'summary' => [
                'total_income'  => (float) $totalIncome,
                'total_expense' => (float) $totalExpense,
                'balance'       => (float) ($totalIncome - $totalExpense),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id'  => 'nullable|exists:cash_categories,id',
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0',
            'type'         => 'required|in:income,expense',
            'date'         => 'required|date',
            'reference_no' => 'nullable|string',
            'attachment'   => 'nullable|file|max:4096',
        ]);
        $data['created_by']  = $request->user()->id;
        $data['status']      = 'approved';
        $data['approved_by'] = $request->user()->id;

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('cashflow', 'public');
        }

        $cf = CashFlow::create($data);
        return response()->json(['data' => $cf->load('category')], 201);
    }

    public function update(Request $request, CashFlow $cashFlow)
    {
        $data = $request->validate([
            'category_id'  => 'sometimes|nullable|exists:cash_categories,id',
            'description'  => 'sometimes|string|max:255',
            'amount'       => 'sometimes|numeric|min:0',
            'type'         => 'sometimes|in:income,expense',
            'date'         => 'sometimes|date',
            'reference_no' => 'sometimes|nullable|string',
            'status'       => 'sometimes|in:pending,approved,rejected',
        ]);
        if (($data['status'] ?? null) === 'approved') {
            $data['approved_by'] = $request->user()->id;
        }
        $cashFlow->update($data);
        return response()->json(['data' => $cashFlow->fresh()->load('category')]);
    }

    public function destroy(CashFlow $cashFlow)
    {
        $cashFlow->delete();
        return response()->json(['message' => 'Dihapus']);
    }

    public function export(Request $request)
    {
        $month = $request->input('month');
        $year  = $request->input('year');

        $rows = CashFlow::with(['creator:id,name', 'category:id,name', 'approver:id,name'])
            ->when($month, fn($q,$m) => $q->whereMonth('date', $m))
            ->when($year,  fn($q,$y) => $q->whereYear('date', $y))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $headers = ['Tanggal', 'Tipe', 'Kategori', 'Deskripsi', 'Jumlah', 'Reference', 'Status', 'Dibuat oleh', 'Disetujui oleh'];

        $data = $rows->map(fn($r) => [
            $r->date->format('Y-m-d'),
            ucfirst($r->type),
            $r->category->name ?? '-',
            $r->description,
            (float) $r->amount,
            $r->reference_no ?? '',
            $r->status,
            $r->creator->name ?? '-',
            $r->approver->name ?? '',
        ]);

        $period = $month && $year
            ? "$year-" . str_pad((string) $month, 2, '0', STR_PAD_LEFT)
            : now()->format('Y-m-d');

        return CsvExporter::stream($data, $headers, "cashflow-$period.csv");
    }

    public function exportPdf(Request $request)
    {
        $month = $request->input('month');
        $year  = $request->input('year');

        $items = CashFlow::with(['creator:id,name', 'category:id,name'])
            ->when($month, fn($q,$m) => $q->whereMonth('date', $m))
            ->when($year,  fn($q,$y) => $q->whereYear('date', $y))
            ->where('status', 'approved')
            ->orderBy('date', 'desc')
            ->get();

        $totalIncome  = (float) $items->where('type', 'income')->sum('amount');
        $totalExpense = (float) $items->where('type', 'expense')->sum('amount');

        $period = $month && $year
            ? Carbon::createFromDate($year, $month)->locale('id')->translatedFormat('F Y')
            : now()->format('Y');

        $pdf = Pdf::loadView('reports.cashflow', [
            'items'        => $items,
            'period'       => $period,
            'totalIncome'  => $totalIncome,
            'totalExpense' => $totalExpense,
            'generatedBy'  => $request->user()->name,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("cashflow-{$period}.pdf");
    }
}
