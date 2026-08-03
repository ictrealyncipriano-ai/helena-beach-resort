<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
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
    </style>
</head>
<body>

    <div class="watermark">@if($inquiry->isPaid())PAID@elseUNPAID@endif</div>

    <div class="top-bar">
        <h1>{{ App\Models\SiteSetting::getValue('site_name', 'Helena Beach Resort') }}</h1>
        <p>{{ App\Models\SiteSetting::getValue('address', 'Purok Buyan, Brgy. Dinahican, Infanta, Quezon') }}</p>
        <p>{{ App\Models\SiteSetting::getValue('contact_phone', '') }} &nbsp;|&nbsp; {{ App\Models\SiteSetting::getValue('contact_email', 'ict.realyncipriano@gmail.com') }}</p>
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

    @php
        $nights = null; $rate = null; $qty = 1;
        if ($inquiry->check_in && $inquiry->check_out) {
            $nights = max((int) $inquiry->check_in->diffInDays($inquiry->check_out), 1);
            $qty = $nights;
        }
        if ($inquiry->booking_type === 'day_tour' && $inquiry->cottage) {
            $rate = $inquiry->cottage->rate_daytour; $qty = 1;
        } elseif ($inquiry->booking_type === 'overnight' && $inquiry->cottage) {
            $rate = $inquiry->cottage->rate_overnight;
        }
        $lineTotal = $rate ? $rate * $qty : $inquiry->total_amount;
        $desc = $inquiry->booking_type === 'day_tour' ? 'Day Tour' : 'Overnight Stay';
    @endphp

    <div class="booking-summary">
        <strong>{{ $inquiry->cottage?->name ?? 'Cottage' }}</strong>
        <span class="sep">|</span> {{ $desc }}
        @if($inquiry->check_in && $inquiry->check_out)
            <span class="sep">|</span>
            {{ $inquiry->check_in->format('M d, Y') }} &mdash; {{ $inquiry->check_out->format('M d, Y') }}
        @endif
        @if($inquiry->pax)
            <span class="sep">|</span>
            {{ $inquiry->pax }} {{ $inquiry->pax > 1 ? 'Guests' : 'Guest' }}
        @endif
    </div>

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
            <tr>
                <td class="num">1</td>
                <td>
                    <strong>{{ $inquiry->cottage?->name ?? 'Cottage' }}</strong>
                    @if($nights)
                        <br><span style="font-size:9px;color:#6b7280;">{{ $desc }} &mdash; {{ $nights }} {{ $nights > 1 ? 'nights' : 'night' }}</span>
                    @endif
                </td>
                <td>{{ $qty }}</td>
                <td>₱{{ number_format($rate ?? 0, 2) }}</td>
                <td>₱{{ number_format($lineTotal ?? 0, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="subtotal">
                <td colspan="4">Subtotal</td>
                <td>₱{{ number_format($lineTotal ?? 0, 2) }}</td>
            </tr>
            @if(($inquiry->total_amount ?? 0) != ($lineTotal ?? 0))
            <tr>
                <td colspan="4" style="font-size:9px;color:#6b7280;">Adjustment</td>
                <td>₱{{ number_format(($inquiry->total_amount ?? 0) - ($lineTotal ?? 0), 2) }}</td>
            </tr>
            @endif
            <tr class="total">
                <td colspan="4">Total Due</td>
                <td>₱{{ number_format($inquiry->total_amount ?? 0, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="terms">
        @if($inquiry->isPaid())
            <strong>Payment Status:</strong> Paid@if($inquiry->payment_method) via {{ ucfirst($inquiry->payment_method) }}@endif
            @if($inquiry->paid_at) on {{ $inquiry->paid_at->format('M d, Y') }}@endif.<br>
        @else
            <strong>Payment Terms:</strong> Full payment due upon booking confirmation. Pay online via GCash, Maya, or card, or settle via Bank Transfer / Cash on site.<br>
        @endif
        <strong>Reference Code:</strong> {{ $inquiry->reference_code }}
        &nbsp;&middot;&nbsp;
        <strong>Guests:</strong> {{ $inquiry->pax ?? '—' }}
    </div>

    <div class="footer">
        <strong>Thank you for choosing Helena Beach Resort!</strong><br>
        Invoice INV-{{ $inquiry->reference_code }} &nbsp;|&nbsp; Generated on {{ now()->format('M d, Y \a\t h:i A') }}
    </div>

</body>
</html>