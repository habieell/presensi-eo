<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Alur Kas {{ $period }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Helvetica', Arial, sans-serif; color: #1e1b4b; font-size: 10pt; }
    .header { padding: 20px 0; border-bottom: 3px solid #7c3aed; margin-bottom: 16px; }
    .header h1 { color: #7c3aed; font-size: 22pt; font-weight: bold; }
    .header .subtitle { color: #64748b; font-size: 10pt; margin-top: 4px; }

    .summary-cards { display: table; width: 100%; margin-bottom: 16px; border-spacing: 8px 0; }
    .summary-cards > div { display: table-cell; padding: 12px; border-radius: 8px; }
    .card-income  { background: #d1fae5; }
    .card-expense { background: #fee2e2; }
    .card-balance { background: #ede9fe; }
    .summary-cards .label { font-size: 8pt; text-transform: uppercase; font-weight: bold; }
    .summary-cards .value { font-size: 14pt; font-weight: bold; margin-top: 4px; }
    .text-emerald { color: #065f46; }
    .text-rose    { color: #991b1b; }
    .text-brand   { color: #5b21b6; }

    table { width: 100%; border-collapse: collapse; font-size: 9pt; }
    thead { background: linear-gradient(135deg, #7c3aed, #5b21b6); color: white; }
    thead th { padding: 8px 6px; text-align: left; font-weight: bold; font-size: 8pt; text-transform: uppercase; }
    tbody td { padding: 6px; border-bottom: 1px solid #e5e7eb; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    .right { text-align: right; }
    .income { color: #059669; font-weight: bold; }
    .expense { color: #dc2626; font-weight: bold; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 7.5pt; font-weight: bold; }
    .badge-approved { background: #d1fae5; color: #065f46; }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .badge-rejected { background: #fee2e2; color: #991b1b; }
    .footer { margin-top: 20px; text-align: center; color: #9ca3af; font-size: 8pt; }
</style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'Presensi') }}</h1>
        <p class="subtitle">Laporan Alur Kas — Periode {{ $period }}</p>
    </div>

    <div class="summary-cards">
        <div class="card-income">
            <div class="label text-emerald">Total Pemasukan</div>
            <div class="value text-emerald">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
        </div>
        <div class="card-expense">
            <div class="label text-rose">Total Pengeluaran</div>
            <div class="value text-rose">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
        </div>
        <div class="card-balance">
            <div class="label text-brand">Saldo</div>
            <div class="value text-brand">Rp {{ number_format($totalIncome - $totalExpense, 0, ',', '.') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 28px;">#</th>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th class="right">Jumlah</th>
                <th>Status</th>
                <th>Dibuat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $r->date->format('d M Y') }}</td>
                    <td>
                        @if ($r->type === 'income')
                            <span style="color: #059669;">▲ Income</span>
                        @else
                            <span style="color: #dc2626;">▼ Expense</span>
                        @endif
                    </td>
                    <td>{{ $r->category->name ?? '-' }}</td>
                    <td>{{ $r->description }}</td>
                    <td class="right {{ $r->type === 'income' ? 'income' : 'expense' }}">
                        {{ $r->type === 'income' ? '+' : '-' }} Rp {{ number_format((float)$r->amount, 0, ',', '.') }}
                    </td>
                    <td><span class="badge badge-{{ $r->status }}">{{ $r->status }}</span></td>
                    <td>{{ $r->creator->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align: center; padding: 24px; color: #9ca3af;">Tidak ada transaksi</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} {{ config('app.name') }} — Generated on {{ now()->format('d M Y H:i:s') }} by {{ $generatedBy }}
    </div>
</body>
</html>
