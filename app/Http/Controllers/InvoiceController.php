<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GuardsBookingAccess;
use App\Models\Inquiry;
use App\Services\PricingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Invoice display and PDF download for confirmed bookings.
 */
class InvoiceController extends Controller
{
    use GuardsBookingAccess;

    /** Display invoice in-browser (HTML) */
    public function show(Inquiry $inquiry): View
    {
        $this->authorizeBookingAccess($inquiry);

        abort_if($inquiry->status !== Inquiry::STATUS_CONFIRMED, 404);

        return view('pages.invoice', [
            'inquiry' => $inquiry,
            ...$this->buildLineItems($inquiry),
        ]);
    }

    /** Download invoice as PDF */
    public function download(Inquiry $inquiry): Response
    {
        $this->authorizeBookingAccess($inquiry);

        abort_if($inquiry->status !== Inquiry::STATUS_CONFIRMED, 404);

        $pdf = Pdf::loadView('pages.invoice', [
            'inquiry' => $inquiry,
            ...$this->buildLineItems($inquiry),
        ]);

        return $pdf->download("invoice-{$inquiry->reference_code}.pdf");
    }

    /**
     * Single source of truth for the invoice's line items. Computes the
     * peak-aware per-night/day rate exactly like the booking flow priced it
     * (Cottage::rateFor), so the invoice can never disagree with the charged
     * total. Both the HTML view and the PDF consume this.
     *
     * @return array{items: array<int, array{desc:string, qty:int, rate:string|int|float|null, total:string}>, subtotal:string}
     */
    private function buildLineItems(Inquiry $inquiry): array
    {
        $inquiry->loadMissing('cottage');
        $pricing = app(PricingService::class);

        $items = [];

        if ($inquiry->booking_type === Inquiry::TYPE_DAY_TOUR && $inquiry->cottage && $inquiry->check_in) {
            $rate = $inquiry->cottage->rateFor($inquiry->check_in, Inquiry::TYPE_DAY_TOUR);
            $line = formatPrice($rate, 2, false);

            $items[] = [
                'desc' => 'Day Tour — '.$inquiry->check_in->format('M d, Y'),
                'qty' => 1,
                'rate' => $rate,
                'total' => $line,
            ];
        } elseif ($inquiry->booking_type === Inquiry::TYPE_OVERNIGHT && $inquiry->cottage && $inquiry->check_in && $inquiry->check_out) {
            $breakdown = $pricing->nightlyBreakdown($inquiry->cottage, $inquiry->check_in, $inquiry->check_out);
            foreach ($breakdown as $night) {
                $line = formatPrice($night['rate'], 2, false);
                $items[] = [
                    'desc' => 'Overnight — '.$night['date'],
                    'qty' => 1,
                    'rate' => $night['rate'],
                    'total' => $line,
                ];
            }
        }

        // If the cottage was soft-deleted (nullOnDelete) or the stay data is
        // incomplete, the per-night rates are unavailable; fall back to the
        // recorded total so the invoice never renders ₱0.00 for a real booking.
        if ($items === []) {
            return [
                'items' => [[
                    'desc' => $inquiry->booking_type === Inquiry::TYPE_DAY_TOUR ? 'Day Tour' : 'Accommodation',
                    'qty' => 1,
                    'rate' => $inquiry->total_amount,
                    'total' => (string) $inquiry->total_amount,
                ]],
                'subtotal' => (string) $inquiry->total_amount,
            ];
        }

        $subtotal = formatPrice(array_sum(array_map(fn ($item) => (float) $item['total'], $items)), 2, false);

        return ['items' => $items, 'subtotal' => $subtotal];
    }
}
