<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice — {{ $inquiry->reference_code }}</title>
    <style>
        @page { margin: 30px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #0d9488;
            margin-bottom: 20px;
        }
        .header h1 { color: #0d9488; margin: 0 0 4px; font-size: 22px; }
        .header p { color: #6b7280; margin: 0; font-size: 11px; }

        .invoice-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .invoice-meta .box {
            padding: 10px 14px;
            background: #f9fafb;
            border-radius: 6px;
        }
        .invoice-meta .box p { margin: 2px 0; }
        .invoice-meta .label { color: #6b7280; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        .invoice-meta .value { font-weight: 600; font-size: 13px; }
        .invoice-meta .value.mono { font-family: 'DejaVu Sans Mono', monospace; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th {
            background: #0d9488;
            color: white;
            text-align: left;
            padding: 8px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
        .text-right { text-align: right; }
        .total-row td { font-weight: 700; font-size: 14px; border-bottom: 2px solid #0d9488; padding-top: 12px; }

        .footer {
            text-align: center;
            color: #9ca3af;
            font-size: 10px;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            background: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Helena Beach Resort</h1>
        <p>Purok Buyan, Brgy. Dinahican, Infanta, Quezon</p>
        <p>helenabeachresort@example.com</p>
    </div>

    <div class="invoice-meta">
        <div class="box">
            <p class="label">Invoice No</p>
            <p class="value mono">INV-{{ $inquiry->reference_code }}</p>
        </div>
        <div class="box">
            <p class="label">Date Issued</p>
            <p class="value">{{ $inquiry->updated_at->format('M d, Y') }}</p>
        </div>
        <div class="box">
            <p class="label">Status</p>
            <p><span class="badge">Confirmed</span></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Rate</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
                $nights = null;
                $rate = null;
                $qty = 1;
                if ($inquiry->check_in && $inquiry->check_out) {
                    $nights = max((int) $inquiry->check_in->diffInDays($inquiry->check_out), 1);
                    $qty = $nights;
                }
                if ($inquiry->booking_type === 'day_tour' && $inquiry->cottage) {
                    $rate = $inquiry->cottage->rate_daytour;
                    $qty = 1;
                } elseif ($inquiry->booking_type === 'overnight' && $inquiry->cottage) {
                    $rate = $inquiry->cottage->rate_overnight;
                }
                $lineTotal = $rate ? $rate * $qty : $inquiry->total_amount;
            @endphp
            <tr>
                <td>
                    <strong>{{ $inquiry->cottage?->name ?? 'Cottage' }}</strong>
                    <br>
                    <span style="font-size:11px;color:#6b7280;">
                        {{ $inquiry->booking_type === 'day_tour' ? 'Day Tour' : 'Overnight' }}
                        @if($inquiry->check_in && $inquiry->check_out)
                            &mdash; {{ $inquiry->check_in->format('M d') }} to {{ $inquiry->check_out->format('M d, Y') }}
                        @endif
                    </span>
                </td>
                <td class="text-right">{{ $qty }}</td>
                <td class="text-right">₱{{ number_format($rate ?? 0, 2) }}</td>
                <td class="text-right">₱{{ number_format($lineTotal ?? 0, 2) }}</td>
            </tr>
        </tbody>
        <tfoot>
            @if($inquiry->pax)
            <tr>
                <td colspan="4" style="padding-top:12px;font-size:11px;color:#6b7280;">
                    <strong>Guests:</strong> {{ $inquiry->pax }}
                    &nbsp;&nbsp;&nbsp;
                    <strong>Reference:</strong> {{ $inquiry->reference_code }}
                </td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" class="text-right">Total Amount Due:</td>
                <td class="text-right">₱{{ number_format($inquiry->total_amount ?? 0, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Thank you for choosing Helena Beach Resort!</p>
        <p>Invoice INV-{{ $inquiry->reference_code }} | Generated on {{ now()->format('M d, Y \a\t h:i A') }}</p>
    </div>
</body>
</html>
