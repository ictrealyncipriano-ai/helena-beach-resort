<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportFilterRequest;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Traits\QueriesByMonth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV report exports for the admin panel. Payloads are streamed to the
 * browser so large datasets never balloon memory.
 */
class ExportController extends Controller
{
    use QueriesByMonth;
    public function index(): View
    {
        $this->authorize('viewAny', Inquiry::class);

        return view('admin.exports.index');
    }

    public function inquiries(ExportFilterRequest $request): StreamedResponse
    {
        $this->authorize('viewAny', Inquiry::class);

        $query = $this->inquiriesQuery($request)->select([
            'id', 'reference_code', 'name', 'email', 'phone', 'booking_type',
            'status', 'source', 'check_in', 'check_out', 'pax', 'total_amount',
            'amount_paid', 'deposit_paid_at', 'fully_paid_at', 'payment_method', 'refunded_at', 'created_at',
        ])->orderBy('id');

        $headers = ['Reference', 'Name', 'Email', 'Phone', 'Type', 'Status', 'Source', 'Check In', 'Check Out', 'Pax', 'Total (PHP)', 'Paid (PHP)', 'Payment Method', 'Paid At', 'Refunded At', 'Created At'];

        // Stream row-by-row via cursor so a 10k-row export never hydrates
        // the full table before the first byte is sent.
        return response()->streamDownload(function () use ($query, $headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(fn ($h) => static::csvCell($h), $headers));
            foreach ($query->cursor() as $i) {
                fputcsv($out, array_map(fn ($cell) => static::csvCell(is_scalar($cell) ? $cell : (string) $cell), [
                    $i->reference_code, $i->name, $i->email, $i->phone,
                    $i->booking_type ? str_replace('_', ' ', ucfirst($i->booking_type)) : '',
                    $i->status, $i->source,
                    $i->check_in?->toDateString() ?? '', $i->check_out?->toDateString() ?? '',
                    $i->pax, $i->total_amount, $i->amount_paid,
                    $i->paymentMethodLabel(),
                    ($i->fully_paid_at ?? $i->deposit_paid_at)?->toDateTimeString() ?? '', $i->refunded_at?->toDateTimeString() ?? '',
                    $i->created_at?->toDateTimeString() ?? '',
                ]));
            }
            fclose($out);
        }, 'inquiries.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function revenue(ExportFilterRequest $request): StreamedResponse
    {
        $this->authorize('viewAny', Inquiry::class);

        // Aggregated by month+cottage (bounded rows); plain get() is fine.
        $rows = $this->revenueQuery($request)->get();

        return $this->download('revenue.csv', ['Period', 'Cottage', 'Bookings', 'Revenue (PHP)'], $rows->map(fn ($r) => [
            $r->period, $r->cottage_name, $r->bookings, $r->total,
        ]));
    }

    public function guests(ExportFilterRequest $request): StreamedResponse
    {
        $this->authorize('viewAny', Guest::class);

        $headers = ['id', 'Name', 'Email', 'Phone', 'Notes', 'Stays', 'Last Stay', 'Inquiries', 'Paid', 'Refunded', 'Failed', 'Revenue (PHP)', 'Created At'];

        return response()->streamDownload(function () use ($request, $headers) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_map(fn ($h) => static::csvCell($h), $headers));
            foreach ($this->guestsQuery($request)->orderBy('guests.id')->cursor() as $g) {
                fputcsv($out, array_map(fn ($cell) => static::csvCell(is_scalar($cell) ? $cell : (string) $cell), [
                    $g->id, $g->name, $g->email, $g->phone, $g->notes,
                    $g->total_stays, $g->last_stay_at?->format('Y-m-d') ?? '',
                    $g->inquiries_count, $g->paid_count, $g->refunded_count, $g->failed_count,
                    $g->paid_amount ?? 0, $g->created_at?->toDateTimeString() ?? '',
                ]));
            }
            fclose($out);
        }, 'guests.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Render the inquiries report as a PDF-style document in the browser.
     */
    public function inquiriesView(ExportFilterRequest $request): View
    {
        $this->authorize('viewAny', Inquiry::class);

        // Paginate the rows but keep whole-range totals: aggregates run as
        // SQL over a clone of the filtered query, never by hydrating rows.
        $rows = $this->inquiriesQuery($request)->with('cottage')->paginate(50)->withQueryString();

        $statusCounts = $this->inquiriesQuery($request)
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $data = [
            'rows' => $rows,
            'totalCount' => $this->inquiriesQuery($request)->count(),
            'totalAmount' => $this->inquiriesQuery($request)->sum('total_amount'),
            'totalPaid' => $this->inquiriesQuery($request)->sum('amount_paid'),
            'statusCounts' => $statusCounts,
            'from' => $request->from,
            'to' => $request->to,
            'status' => $request->status,
            'title' => 'Inquiries Report',
        ];

        return view('admin.exports.report-inquiries', $data);
    }

    /**
     * Render the revenue report as a PDF-style document in the browser.
     */
    public function revenueView(ExportFilterRequest $request): View
    {
        $this->authorize('viewAny', Inquiry::class);

        $rows = $this->revenueQuery($request)->get();

        $grandTotal = $rows->sum('total');
        $grandBookings = $rows->sum('bookings');

        $data = [
            'rows' => $rows,
            'grandTotal' => $grandTotal,
            'grandBookings' => $grandBookings,
            'from' => $request->from,
            'to' => $request->to,
            'title' => 'Revenue Report',
        ];

        return view('admin.exports.report-revenue', $data);
    }

    /**
     * Render the guests report as a PDF-style document in the browser.
     */
    public function guestsView(ExportFilterRequest $request): View
    {
        $this->authorize('viewAny', Guest::class);

        // Paginate the rows; the footer totals stay whole-range via SQL so a
        // large guest book never hydrates just to render sums.
        $rows = $this->guestsQuery($request)->paginate(50)->withQueryString();

        $data = [
            'rows' => $rows,
            'totalCount' => $this->guestWindow($request)->count(),
            'totalStays' => $this->guestWindow($request)->sum('total_stays'),
            'totalRevenue' => $this->guestScopedInquiries($request)->where('amount_paid', '>', 0)->sum('amount_paid'),
            'totalInquiries' => $this->guestScopedInquiries($request)->count(),
            'totalPaid' => $this->guestScopedInquiries($request)->where('amount_paid', '>', 0)->count(),
            'totalRefunded' => $this->guestScopedInquiries($request)->whereNotNull('refunded_at')->count(),
            'totalFailed' => $this->guestScopedInquiries($request)->whereNotNull('payment_failed_at')->count(),
            'title' => 'Guests Report',
            'from' => $request->from,
            'to' => $request->to,
        ];

        return view('admin.exports.report-guests', $data);
    }

    /**
     * Inquiries report query, honoring the from/to/status filters shared by
     * the CSV export and the in-browser report view.
     */
    private function inquiriesQuery(Request $request): \Illuminate\Database\Eloquent\Builder
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

        return $query;
    }

    /**
     * Revenue report query: paid bookings grouped by month and cottage.
     *
     * Revenue is attributed to the settlement month
     * COALESCE(fully_paid_at, deposit_paid_at) — the same rule as the
     * dashboard — so fully-paid non-deposit bookings appear, each booking
     * lands in exactly one month, and rows with no paid date at all are
     * preserved whenever no date filter is applied.
     */
    private function revenueQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $settled = 'COALESCE(fully_paid_at, deposit_paid_at)';
        $monthExpr = $this->monthExpression($settled);

        $query = Inquiry::query()
            ->select(DB::raw("{$monthExpr} as period"), 'cottages.name as cottage_name', DB::raw('sum(amount_paid) as total'), DB::raw('count(*) as bookings'))
            ->join('cottages', 'cottages.id', '=', 'inquiries.cottage_id')
            ->where('amount_paid', '>', 0)
            ->groupBy('period', 'cottages.name')
            ->orderBy('period')
            ->orderBy('cottages.name');

        if ($request->filled('from')) {
            $query->whereRaw("{$settled} >= ?", [$request->from]);
        }

        if ($request->filled('to')) {
            // whereDate(<= to) covers the whole day; the raw equivalent is
            // a strict upper bound on the day after.
            $query->whereRaw("{$settled} < ?", [date('Y-m-d', strtotime($request->to.' +1 day'))]);
        }

        return $query;
    }

    /**
     * Guest lifetime stats query shared by the CSV export and the in-browser
     * report. Honors the same from/to window as the other reports (on guest
     * creation).
     */
    private function guestsQuery(?Request $request = null): \Illuminate\Database\Eloquent\Builder
    {
        $query = Guest::withCount(['inquiries as inquiries_count' => fn ($q) => $q->whereNull('deleted_at')])
            ->withCount(['inquiries as paid_count' => fn ($q) => $q->where('amount_paid', '>', 0)])
            ->withCount(['inquiries as failed_count' => fn ($q) => $q->whereNotNull('payment_failed_at')])
            ->withCount(['inquiries as refunded_count' => fn ($q) => $q->whereNotNull('refunded_at')])
            ->withSum(['inquiries as paid_amount' => fn ($q) => $q->where('amount_paid', '>', 0)], 'amount_paid')
            ->orderBy('created_at');

        if ($request?->filled('from')) {
            $query->whereDate('guests.created_at', '>=', $request->from);
        }

        if ($request?->filled('to')) {
            $query->whereDate('guests.created_at', '<=', $request->to);
        }

        return $query;
    }

    /**
     * Guest lifetime stats shared by the CSV export and the in-browser report.
     * Honors the same from/to window as the other reports (on guest creation).
     */
    private function guestsData(?Request $request = null): Collection
    {
        return $this->guestsQuery($request)->get();
    }

    /**
     * The guest date window shared by guestsQuery(), honoring from/to on
     * guest creation. Extracted so whole-range footer aggregates reuse the
     * exact same window without hydrating guest rows.
     */
    private function guestWindow(?Request $request = null): \Illuminate\Database\Eloquent\Builder
    {
        $query = Guest::query();

        if ($request?->filled('from')) {
            $query->whereDate('guests.created_at', '>=', $request->from);
        }

        if ($request?->filled('to')) {
            $query->whereDate('guests.created_at', '<=', $request->to);
        }

        return $query;
    }

    /**
     * Inquiries belonging to windowed guests (soft-deleted rows excluded by
     * the global scope, mirroring the withCount/withSum relations). Basis
     * for the whole-range guests-report footer aggregates.
     */
    private function guestScopedInquiries(?Request $request = null): \Illuminate\Database\Eloquent\Builder
    {
        return Inquiry::query()->whereHas('guest', function ($q) use ($request) {
            $q->whereNull('guests.deleted_at');

            if ($request?->filled('from')) {
                $q->whereDate('guests.created_at', '>=', $request->from);
            }

            if ($request?->filled('to')) {
                $q->whereDate('guests.created_at', '<=', $request->to);
            }
        });
    }

    /**
     * Stream a CSV download. Every cell is escaped against CSV formula
     * injection (Excel treats = + - @ prefixes as formulas) by prefixing a
     * single quote, and utf-8 BOM is emitted so Excel reads headers correctly.
     */
    private function download(string $filename, array $headers, Collection $rows): StreamedResponse
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
    private static function csvCell(mixed $value): string
    {
        $value = (string) $value;

        return in_array(substr($value, 0, 1), ['=', '+', '-', '@'], true)
            ? "'".$value
            : $value;
    }
}
