<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Invoice — {{ $inquiry->reference_code }}</title>
    <style>
        @page { margin: 15mm 20mm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #374151;
            line-height: 1.5;
            margin: 0 auto;
            padding: 0;
            max-width: 800px;
        }

        .top-bar {
            background: #0d9488;
            margin: 0;
            padding: 8mm 20mm;
            color: white;
        }
        .top-bar h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .top-bar p {
            margin: 2px 0 0;
            font-size: 9px;
            opacity: 0.85;
        }

        .invoice-title {
            text-align: center;
            margin: 30px 0 24px;
            position: relative;
        }
        .invoice-title h2 {
            font-size: 24px;
            color: #0d9488;
            margin: 0 0 4px;
            font-weight: 700;
            letter-spacing: 4px;
        }
        .invoice-title .sub {
            font-size: 9px;
            color: #9ca3af;
            letter-spacing: 1px;
        }
        .invoice-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 2px;
            background: #0d9488;
            margin: 10px auto 0;
        }

        .meta-grid {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }
        .meta-grid .col { flex: 1; }
        .meta-grid .col.right { text-align: right; }
        .meta-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #9ca3af;
            margin-bottom: 2px;
        }
        .meta-value {
            font-size: 11px;
            font-weight: 600;
            color: #1f2937;
        }
        .meta-value.mono { font-family: 'DejaVu Sans Mono', monospace; }
        .meta-value .badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            background: #d1fae5;
            color: #065f46;
        }

        .booking-summary {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 24px;
            font-size: 9px;
        }
        .booking-summary strong { color: #1f2937; }
        .booking-summary .sep { color: #d1d5db; margin: 0 8px; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items thead th {
            background: #0d9488; color: white; padding: 7px 10px;
            font-size: 9px; text-transform: uppercase; letter-spacing: 0.6px;
            text-align: left;
        }
        table.items thead th:last-child { text-align: right; }
        table.items thead th:nth-child(3) { text-align: right; }
        table.items thead th:nth-child(4) { text-align: right; }
        table.items tbody td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        table.items tbody td:last-child { text-align: right; }
        table.items tbody td:nth-child(3) { text-align: right; }
        table.items tbody td:nth-child(4) { text-align: right; }
        table.items tbody td.num { text-align: center; width: 30px; color: #9ca3af; }
        table.items tbody tr:last-child td { border-bottom: none; }

        table.items tfoot td { padding: 6px 10px; font-size: 10px; }
        table.items tfoot td:last-child { text-align: right; }
        table.items tfoot tr.subtotal td { border-top: 1px solid #d1d5db; padding-top: 10px; font-weight: 600; }
        table.items tfoot tr.total td {
            border-top: 2px solid #0d9488; padding-top: 8px;
            font-size: 14px; font-weight: 700; color: #0d9488;
        }
        table.items tfoot tr.total td:last-child { font-size: 15px; }

        .terms {
            border-top: 1px solid #e5e7eb; padding-top: 14px; margin-top: 10px;
            font-size: 9px; color: #6b7280;
        }
        .terms strong { color: #374151; }

        .footer {
            text-align: center; color: #9ca3af; font-size: 8px;
            margin-top: 30px; padding-top: 12px; border-top: 1px solid #e5e7eb;
        }
        .footer strong { color: #6b7280; }

        .watermark {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 80px; color: rgba(13, 148, 136, 0.04);
            font-weight: 700; letter-spacing: 10px;
            pointer-events: none; z-index: -1;
        }

        /* Screen-only chrome: hidden by default so DomPDF / print output
           is byte-identical to before. Enabled only under @media screen. */
        .action-bar { display: none; }
        .table-wrap { width: 100%; }

        @media print {
            .action-bar { display: none !important; }
        }

        {{-- DomPDF renders with screen as its default media type, so every
             @media screen rule below would leak into the PDF while @media
             print is ignored. Exclude the whole screen layer when $isPdf. --}}
        @unless($isPdf ?? false)
        /* Screen foundation (all widths): page backdrop, sheet background,
           and the action bar visible on every screen but never in print/PDF
           (base stays display:none; print forces it hidden). */
        @media screen {
            html { background: #eef2f5; }
            body { background: #ffffff; overflow-x: hidden; }
            .action-bar {
                display: flex;
                gap: 8px;
                align-items: stretch;
                position: sticky;
                top: 0;
                z-index: 10;
                background: #ffffff;
                border-bottom: 1px solid #e5e7eb;
                padding: 10px 0;
            }
            .action-bar a {
                flex: 1;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 44px;
                padding: 10px 12px;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                line-height: 1.2;
            }
            .action-bar .back {
                color: #0d9488;
                border: 1px solid #99f6e4;
                background: #ffffff;
            }
            .action-bar .download {
                color: #ffffff;
                background: #0d9488;
                border: 1px solid #0d9488;
            }
        }

        /* Tablet: roomier gutters, wrapping meta grid, no table scroll. */
        @media screen and (min-width: 641px) and (max-width: 1023px) {
            body {
                padding: 0 24px 40px;
                font-size: 12px;
            }
            .action-bar {
                margin: 0 -24px;
                padding-left: 24px;
                padding-right: 24px;
            }
            .top-bar {
                margin: 0 -24px;
                padding: 24px;
            }
            .top-bar h1 { font-size: 20px; }
            .top-bar p { font-size: 11px; word-break: break-word; }
            .meta-grid {
                flex-wrap: wrap;
                gap: 20px;
                margin-bottom: 24px;
            }
            .meta-value.mono { word-break: break-all; }
            .booking-summary { line-height: 1.8; font-size: 11px; }
            .table-wrap {
                overflow: visible;
                border: none;
                margin-bottom: 20px;
            }
            .terms { font-size: 11px; }
            .footer { font-size: 10px; }
        }

        /* Desktop: centered sheet/card on a soft backdrop. Every value here
           overrides inside screen scope only; the PDF keeps the base rules. */
        @media screen and (min-width: 1024px) {
            body {
                max-width: 880px;
                margin: 24px auto 48px;
                padding: 0 48px 40px;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                box-shadow: 0 8px 30px rgba(15, 23, 42, 0.08);
                font-size: 13px;
            }
            .action-bar {
                margin: 0 -48px;
                padding: 12px 48px;
                border-radius: 12px 12px 0 0;
            }
            .top-bar {
                margin: 0 -48px;
                padding: 28px 48px;
            }
            .top-bar h1 { font-size: 22px; }
            .top-bar p { font-size: 11px; word-break: break-word; }
            .invoice-title { margin: 28px 0 22px; }
            .invoice-title h2 { font-size: 26px; }
            .meta-grid {
                flex-wrap: wrap;
                gap: 24px;
                margin-bottom: 24px;
            }
            .meta-value { font-size: 13px; }
            .meta-value.mono { word-break: break-all; }
            .booking-summary { font-size: 12px; line-height: 1.8; }
            table.items thead th { padding: 9px 12px; font-size: 10px; }
            table.items tbody td { padding: 10px 12px; font-size: 12px; }
            table.items tfoot td { font-size: 12px; }
            .table-wrap {
                overflow: visible;
                border: none;
                margin-bottom: 20px;
            }
            .terms { font-size: 11px; }
            .footer { font-size: 10px; }
        }

        @media screen and (max-width: 640px) {
            body {
                padding: 0 12px 32px;
                font-size: 12px;
                overflow-x: hidden;
            }
            .action-bar {
                display: flex;
                gap: 8px;
                align-items: stretch;
                position: sticky;
                top: 0;
                z-index: 10;
                margin: 0 -12px;
                padding: 10px 12px;
                background: #ffffff;
                border-bottom: 1px solid #e5e7eb;
            }
            .action-bar a {
                flex: 1;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 44px;
                padding: 10px 12px;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                line-height: 1.2;
            }
            .action-bar .back {
                color: #0d9488;
                border: 1px solid #99f6e4;
                background: #ffffff;
            }
            .action-bar .download {
                color: #ffffff;
                background: #0d9488;
                border: 1px solid #0d9488;
            }
            .top-bar {
                margin: 0 -12px;
                padding: 14px 16px;
            }
            .top-bar h1 { font-size: 18px; }
            .top-bar p { font-size: 11px; word-break: break-word; }
            .invoice-title { margin: 22px 0 18px; }
            .invoice-title h2 { font-size: 20px; }
            .meta-grid {
                flex-direction: column;
                gap: 12px;
                margin-bottom: 20px;
            }
            .meta-grid .col.right { text-align: left; }
            .meta-value.mono { word-break: break-all; }
            .booking-summary { line-height: 1.8; font-size: 11px; }
            .table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin-bottom: 20px;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
            }
            .table-wrap table.items { margin-bottom: 0; min-width: 520px; }
            .table-wrap table.items thead th,
            .table-wrap table.items tbody td,
            .table-wrap table.items tfoot td {
                padding-left: 8px;
                padding-right: 8px;
            }
            .watermark { font-size: 48px; letter-spacing: 6px; }
            .terms { font-size: 11px; }
            .footer { font-size: 10px; }
        }
        @endunless
    </style>
</head>
<body>

    @unless($isPdf ?? false)
    <div class="action-bar">
        <a class="back" href="{{ route('booking.portal.show', $inquiry) }}">&larr; Back to Booking</a>
        <a class="download" href="{{ route('invoice.download', $inquiry) }}">Download PDF</a>
    </div>
    @endunless

    <div class="watermark">{{ $inquiry->isPaid() ? 'PAID' : 'UNPAID' }}</div>

    <div class="top-bar">
        <h1>{{ App\Models\SiteSetting::getValue('site_name', config('app.name')) }}</h1>
        <p>{{ App\Models\SiteSetting::getValue('address', '') }}</p>
        <p>{{ App\Models\SiteSetting::getValue('contact_phone', '') }} &nbsp;|&nbsp; {{ App\Models\SiteSetting::getValue('contact_email', config('mail.from.address')) }}</p>
    </div>

    <div class="invoice-title">
        <h2>INVOICE</h2>
        <span class="sub">OFFICIAL RECEIPT</span>
    </div>

    <div class="meta-grid">
        <div class="col">
            <div class="meta-label">Bill To</div>
            <div class="meta-value">{{ $inquiry->name }}</div>
            <div style="font-size:9px;color:#6b7280;margin-top:2px;">
                {{ $inquiry->email }}<br>
                @if($inquiry->phone){{ $inquiry->phone }}@endif
            </div>
        </div>
        <div class="col right">
            <div class="meta-label">Invoice No</div>
            <div class="meta-value mono">INV-{{ $inquiry->reference_code }}</div>
            <div style="margin-top:6px;">
                <div class="meta-label">Date Issued</div>
                <div class="meta-value" style="font-size:10px;">{{ $inquiry->updated_at->format('M d, Y') }}</div>
            </div>
            <div style="margin-top:6px;">
                <div class="meta-label">Status</div>
                <div><span class="badge">{{ $inquiry->isPaid() ? 'Paid' : 'Confirmed' }}</span></div>
            </div>
        </div>
    </div>

    <div class="booking-summary">
        <strong>{{ $inquiry->cottage?->name ?? 'Cottage' }}</strong>
        <span class="sep">|</span> {{ $inquiry->booking_type === 'day_tour' ? 'Day Tour' : 'Overnight Stay' }}
        @if($inquiry->check_in && $inquiry->check_out)
            <span class="sep">|</span>
            {{ $inquiry->check_in->format('M d, Y') }} &mdash; {{ $inquiry->check_out->format('M d, Y') }}
        @endif
        @if($inquiry->pax)
            <span class="sep">|</span>
            {{ $inquiry->pax }} {{ $inquiry->pax > 1 ? 'Guests' : 'Guest' }}
        @endif
    </div>

    <div class="table-wrap">
    <table class="items">
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Description</th>
                <th style="width:50px;">Qty</th>
                <th style="width:70px;">Rate</th>
                <th style="width:80px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>
                    <strong>{{ $inquiry->cottage?->name ?? 'Cottage' }}</strong>
                    <br><span style="font-size:9px;color:#6b7280;">{{ $item['desc'] }}</span>
                </td>
                <td>{{ $item['qty'] }}</td>
                <td>{{ formatPrice($item['rate']) }}</td>
                <td>{{ formatPrice($item['total']) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="subtotal">
                <td colspan="4">Subtotal</td>
                <td>{{ formatPrice($subtotal) }}</td>
            </tr>
            @if(($inquiry->discount_amount ?? 0) > 0)
            <tr>
                <td colspan="4" style="font-size:9px;color:#6b7280;">Promo Discount</td>
                <td>-{{ formatPrice($inquiry->discount_amount) }}</td>
            </tr>
            @endif
            @php
                // Money Migration: exact string reconciliation of the printed
                // totals (Subtotal − Discount + Adjustment = Total Due), never
                // binary float. Display only; persisted totals untouched.
                $adjustment = \App\Support\Money::sub(
                    (string) ($inquiry->total_amount ?? '0.00'),
                    \App\Support\Money::sub(
                        (string) $subtotal,
                        (string) ($inquiry->discount_amount ?? '0.00')
                    )
                );
            @endphp
            @if(\App\Support\Money::cmp($adjustment, '0.00') !== 0)
            <tr>
                <td colspan="4" style="font-size:9px;color:#6b7280;">Adjustment</td>
                <td>{{ formatPrice($adjustment) }}</td>
            </tr>
            @endif
            <tr class="total">
                <td colspan="4">Total Due</td>
                <td>{{ formatPrice($inquiry->total_amount ?? 0) }}</td>
            </tr>
        </tfoot>
    </table>
    </div>

    <div class="terms">
        @if($inquiry->isPaid())
            <strong>Payment Status:</strong> Paid{{ $inquiry->payment_method ? ' via ' . ucfirst($inquiry->payment_method) : '' }}{{ ($inquiry->fully_paid_at ?? $inquiry->deposit_paid_at) ? ' on ' . ($inquiry->fully_paid_at ?? $inquiry->deposit_paid_at)->format('M d, Y') : '' }}.<br>
        @else
            <strong>Payment Terms:</strong> Full payment due upon booking confirmation. Pay online via QR Ph, or settle via Bank Transfer / Cash on site.<br>
        @endif
        <strong>Reference Code:</strong> {{ $inquiry->reference_code }}
        &nbsp;&middot;&nbsp;
        <strong>Guests:</strong> {{ $inquiry->pax ?? '—' }}
    </div>

    <div class="footer">
        <strong>Thank you for choosing {{ App\Models\SiteSetting::getValue('site_name', config('app.name')) }}!</strong><br>
        Invoice INV-{{ $inquiry->reference_code }} &nbsp;|&nbsp; Generated on {{ now()->format('M d, Y \a\t h:i A') }}
    </div>

</body>
</html>