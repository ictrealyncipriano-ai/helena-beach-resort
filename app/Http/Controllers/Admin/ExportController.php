<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV report exports for the admin panel. Payloads are streamed to the
 * browser so large datasets never balloon memory.
 */
class ExportController extends Controller
{
    public function index()
    {
        return view('admin.exports.index');
    }

    public function inquiries(Request $request): StreamedResponse
    {
        $query = Inquiry::query()->latest('created_at');

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rows = $query->get(['id', 'reference_code', 'name', 'email', 'phone', 'booking_type', 'status', 'source', 'check_in', 'check_out', 'pax', 'total_amount', 'paid_at', 'paid_amount', 'payment_method', 'refunded_at', 'created_at']);

        $headers = ['Reference', 'Name', 'Email', 'Phone', 'Type', 'Status', 'Source', 'Check In', 'Check Out', 'Pax', 'Total (PHP)', 'Paid (PHP)', 'Payment Method', 'Paid At', 'Refunded At', 'Created At'];

        return $this->download('inquiries.csv', $headers, $rows->map(fn ($i) => [
            $i->reference_code, $i->name, $i->email, $i->phone,
            $i->booking_type ? str_replace('_', ' ', ucfirst($i->booking_type)) : '',
            $i->status, $i->source,
            $i->check_in?->toDateString() ?? '', $i->check_out?->toDateString() ?? '',
            $i->pax, $i->total_amount, $i->paid_amount,
            $i->paymentMethodLabel(),
            $i->paid_at?->toDateTimeString() ?? '', $i->refunded_at?->toDateTimeString() ?? '',
            $i->created_at?->toDateTimeString() ?? '',
        ]));
    }

    public function revenue(Request $request): StreamedResponse
    {
        $monthExpr = $this->monthExpression('paid_at');

        $query = Inquiry::query()
            ->select(DB::raw("{$monthExpr} as period"), 'cottages.name as cottage_name', DB::raw('sum(paid_amount) as total'), DB::raw('count(*) as bookings'))
            ->join('cottages', 'cottages.id', '=', 'inquiries.cottage_id')
            ->whereNotNull('paid_at')
            ->groupBy('period', 'cottages.name')
            ->orderBy('period')
            ->orderBy('cottages.name');

        if ($request->filled('from')) {
            $query->whereDate('paid_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('paid_at', '<=', $request->to);
        }

        $rows = $query->get();

        return $this->download('revenue.csv', ['Period', 'Cottage', 'Bookings', 'Revenue (PHP)'], $rows->map(fn ($r) => [
            $r->period, $r->cottage_name, $r->bookings, $r->total,
        ]));
    }

    public function guests(Request $request): StreamedResponse
    {
        $rows = Guest::withCount(['inquiries as inquiries_count' => fn ($q) => $q->whereNull('deleted_at')])
            ->withCount(['inquiries as paid_count' => fn ($q) => $q->whereNotNull('paid_at')])
            ->withCount(['inquiries as failed_count' => fn ($q) => $q->whereNotNull('payment_failed_at')])
            ->withCount(['inquiries as refunded_count' => fn ($q) => $q->whereNotNull('refunded_at')])
            ->withSum(['inquiries as paid_amount' => fn ($q) => $q->whereNotNull('paid_at')], 'paid_amount')
            ->orderBy('created_at')
            ->get();

        return $this->download('guests.csv', ['id', 'Name', 'Email', 'Phone', 'Notes', 'Stays', 'Last Stay', 'Inquiries', 'Paid', 'Refunded', 'Failed', 'Revenue (PHP)', 'Created At'], $rows->map(fn ($g) => [
            $g->id, $g->name, $g->email, $g->phone, $g->notes,
            $g->total_stays, $g->last_stay_at?->format('Y-m-d') ?? '',
            $g->inquiries_count, $g->paid_count, $g->refunded_count, $g->failed_count,
            $g->paid_amount ?? 0, $g->created_at?->toDateTimeString() ?? '',
        ]));
    }

    /**
     * Stream a CSV download. Every cell is escaped against CSV formula
     * injection (Excel treats = + - @ prefixes as formulas) by prefixing a
     * single quote, and utf-8 BOM is emitted so Excel reads headers correctly.
     */
    private function download(string $filename, array $headers, $rows): StreamedResponse
    {
        $rows = collect($rows)->map(fn ($row) => array_values((array) $row));

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(fn ($h) => static::csvCell($h), $headers));

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($cell) => static::csvCell(is_scalar($cell) ? $cell : (string) $cell), $row));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Neutralize a value that Excel/Sheets would otherwise evaluate as a
     * formula while keeping the plain label readable.
     */
    private static function csvCell($value): ?string
    {
        $value = (string) $value;

        return in_array(substr($value, 0, 1), ['=', '+', '-', '@'], true)
            ? "'".$value
            : $value;
    }

    private function monthExpression(string $column): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
