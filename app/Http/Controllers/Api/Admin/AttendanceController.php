<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\ActivityLog\CsvExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $q = Attendance::with('user:id,name,email,position,nik,avatar');

        if ($date = $request->input('date'))     $q->whereDate('date', $date);
        if ($month = $request->input('month'))   $q->whereMonth('date', $month);
        if ($year = $request->input('year'))     $q->whereYear('date', $year);
        if ($status = $request->input('status')) $q->where('status', $status);
        if ($userId = $request->input('user_id')) $q->where('user_id', $userId);

        return response()->json($q->orderBy('date', 'desc')->orderBy('time', 'desc')->paginate(20));
    }

    public function export(Request $request)
    {
        $month = $request->input('month');
        $year  = $request->input('year');

        $items = Attendance::with('user:id,name,nik,position')
            ->when($month, fn($q,$m) => $q->whereMonth('date', $m))
            ->when($year,  fn($q,$y) => $q->whereYear('date', $y))
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

        $headers = [
            'Tanggal', 'Hari', 'Waktu', 'Tipe',
            'Nama', 'NIK', 'Posisi',
            'Status', 'Face Verified', 'Score (%)',
            'Latitude', 'Longitude', 'Lokasi',
        ];

        $data = $items->map(fn($r) => [
            $r->date->format('Y-m-d'),
            $r->date->locale('id')->translatedFormat('l'),
            $r->time,
            strtoupper($r->type),
            $r->user->name ?? '-',
            $r->user->nik ?? '-',
            $r->user->position ?? '-',
            $r->status,
            $r->face_verified ? 'YES' : 'NO',
            $r->face_score ? round($r->face_score * 100, 1) : '',
            $r->latitude ?? '',
            $r->longitude ?? '',
            $r->location ?? '',
        ]);

        $period = $month && $year
            ? "$year-" . str_pad((string) $month, 2, '0', STR_PAD_LEFT)
            : now()->format('Y-m-d');

        return CsvExporter::stream($data, $headers, "attendance-$period.csv");
    }

    public function exportPdf(Request $request)
    {
        $month = $request->input('month');
        $year  = $request->input('year');

        $items = Attendance::with('user:id,name,nik,position')
            ->when($month, fn($q,$m) => $q->whereMonth('date', $m))
            ->when($year,  fn($q,$y) => $q->whereYear('date', $y))
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

        $period = $month && $year
            ? Carbon::createFromDate($year, $month)->locale('id')->translatedFormat('F Y')
            : now()->format('Y');

        $pdf = Pdf::loadView('reports.attendance', [
            'items'       => $items,
            'period'      => $period,
            'generatedBy' => $request->user()->name,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("attendance-{$period}.pdf");
    }
}
