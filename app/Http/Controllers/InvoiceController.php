<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Invoice display and PDF download for confirmed bookings.
 */
class InvoiceController extends Controller
{
    /** Display invoice in-browser (HTML) */
    public function show(Inquiry $inquiry)
    {
        abort_if($inquiry->status !== 'confirmed', 404);

        return view('pages.invoice', compact('inquiry'));
    }

    /** Download invoice as PDF */
    public function download(Inquiry $inquiry)
    {
        abort_if($inquiry->status !== 'confirmed', 404);

        $nights = null;
        $subtotal = null;

        if ($inquiry->check_in && $inquiry->check_out) {
            $nights = max((int) $inquiry->check_in->diffInDays($inquiry->check_out), 1);
            if ($inquiry->booking_type === 'day_tour') {
                $subtotal = $inquiry->cottage?->rate_daytour;
            } elseif ($inquiry->booking_type === 'overnight' && $inquiry->cottage) {
                $subtotal = $inquiry->cottage->rate_overnight * $nights;
            }
        }

        $pdf = Pdf::loadView('pages.invoice', [
            'inquiry' => $inquiry,
            'nights' => $nights,
            'subtotal' => $subtotal,
        ]);

        return $pdf->download("invoice-{$inquiry->reference_code}.pdf");
    }
}
