<?php

namespace App\Services\ActivityLog;

use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CsvExporter — export Excel-friendly CSV
 *
 * Fitur:
 *  - BOM UTF-8 (biar emoji & karakter Indonesia tidak rusak di Excel)
 *  - Delimiter `;` (default Excel locale ID/EU). Bisa override ke `,`
 *  - Auto-quote field yang ada delimiter / newline / quote di dalamnya
 *  - Streaming (gak load semua row ke memory)
 */
class CsvExporter
{
    /**
     * @param iterable $rows  iterable of rows (array)
     * @param array    $headers  header row
     * @param string   $filename
     * @param string   $delimiter  default ';' (Excel ID-friendly)
     */
    public static function stream(
        iterable $rows,
        array $headers,
        string $filename,
        string $delimiter = ';'
    ): StreamedResponse {
        return Response::stream(function () use ($rows, $headers, $delimiter) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, $delimiter, '"', '\\');
            foreach ($rows as $row) {
                fputcsv($out, $row, $delimiter, '"', '\\');
            }
            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
