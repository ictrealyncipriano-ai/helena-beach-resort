<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — {{ App\Models\SiteSetting::getValue('site_name', 'Helena Beach Resort') }}</title>
    <style>
        @page { size: A4; margin: 14mm 16mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.5;
            background: #e5e7eb;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px;
            background: #0f172a;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .toolbar button, .toolbar a.btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .toolbar button { background: #0d9488; color: #fff; }
        .toolbar button:hover { background: #0f766e; }
        .toolbar a.btn { background: #334155; color: #e2e8f0; }
        .toolbar a.btn:hover { background: #475569; }

        .sheet {
            width: 860px;
            max-width: calc(100% - 32px);
            margin: 32px auto;
            background: #fff;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18);
            padding: 42px 50px;
            min-height: 1123px;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 3px solid #0d9488;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .report-header .brand { display: flex; align-items: center; gap: 14px; }
        .report-header .brand img {
            width: 52px; height: 52px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        .report-header .brand h1 {
            margin: 0;
            font-size: 19px;
            font-weight: 700;
            color: #0d9488;
            letter-spacing: 0.3px;
        }
        .report-header .brand p {
            margin: 1px 0 0;
            font-size: 9px;
            color: #6b7280;
        }
        .report-header .meta { text-align: right; }
        .report-header .meta h2 {
            margin: 0 0 6px;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .report-header .meta p {
            margin: 2px 0;
            font-size: 9px;
            color: #9ca3af;
        }
        .report-header .meta p strong { color: #374151; font-weight: 600; }

        .summary {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
        }
        .summary .stat {
            flex: 1 1 150px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 14px;
            background: #f9fafb;
        }
        .summary .stat .label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #9ca3af;
            margin-bottom: 3px;
        }
        .summary .stat .value {
            font-size: 15px;
            font-weight: 700;
            color: #0d9488;
        }
        .summary .stat .value.small { font-size: 11px; line-height: 1.6; color: #374151; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data thead th {
            background: #0d9488;
            color: #fff;
            padding: 7px 9px;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            text-align: left;
            border: 1px solid #0f766e;
        }
        table.data tbody td {
            padding: 6px 9px;
            border: 1px solid #e5e7eb;
            font-size: 10px;
            vertical-align: top;
        }
        table.data tbody tr:nth-child(even) td { background: #f8fafc; }
        table.data td.r, table.data th.r { text-align: right; }
        table.data td.c, table.data th.c { text-align: center; }
        table.data td.mono { font-family: 'DejaVu Sans Mono', Consolas, monospace; font-size: 9px; }
        table.data td .sub { font-size: 9px; color: #9ca3af; }

        table.data tfoot td {
            padding: 8px 9px;
            border: 1px solid #0d9488;
            font-size: 11px;
            font-weight: 700;
            color: #0d9488;
            background: #f0fdfa;
        }

        .badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-expired { background: #e5e7eb; color: #4b5563; }

        .empty {
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            padding: 26px;
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            background: #f9fafb;
        }

        .report-footer {
            text-align: center;
            color: #9ca3af;
            font-size: 8px;
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }
        .report-footer strong { color: #6b7280; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet {
                box-shadow: none;
                width: auto;
                max-width: none;
                margin: 0;
                padding: 0;
                min-height: 0;
            }
            table.data thead th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <button type="button" onclick="window.print()">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print / Save as PDF
        </button>
        <a href="{{ route('admin.exports.index') }}" class="btn">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Reports
        </a>
    </div>

    <div class="sheet">
        <div class="report-header">
            <div class="brand">
                <img src="{{ asset('images/logo.jpg') }}" alt="{{ App\Models\SiteSetting::getValue('site_name', 'Helena Beach Resort') }}">
                <div>
                    <h1>{{ App\Models\SiteSetting::getValue('site_name', 'Helena Beach Resort') }}</h1>
                    <p>{{ App\Models\SiteSetting::getValue('address', 'Purok Buyan, Brgy. Dinahican, Infanta, Quezon') }}</p>
                    <p>{{ App\Models\SiteSetting::getValue('contact_phone', '') }}{{ App\Models\SiteSetting::getValue('contact_phone', '') && App\Models\SiteSetting::getValue('contact_email', '') ? ' | ' : '' }}{{ App\Models\SiteSetting::getValue('contact_email', 'ict.realyncipriano@gmail.com') }}</p>
                </div>
            </div>
            <div class="meta">
                <h2>{{ $title }}</h2>
                <p><strong>Generated:</strong> {{ now()->format('M d, Y \a\t h:i A') }}</p>
                <p><strong>Period:</strong>
                    @php
                        $period = 'All time';
                        if (! empty($from) && ! empty($to)) {
                            $period = $from.' &mdash; '.$to;
                        } elseif (! empty($from)) {
                            $period = 'From '.$from;
                        } elseif (! empty($to)) {
                            $period = 'Until '.$to;
                        }
                    @endphp
                    {!! $period !!}
                </p>
                @if (! empty($status))
                    <p><strong>Status:</strong> {{ ucfirst($status) }}</p>
                @endif
            </div>
        </div>

        @yield('report-content')

        <div class="report-footer">
            <strong>{{ App\Models\SiteSetting::getValue('site_name', 'Helena Beach Resort') }}</strong>
            &nbsp;&middot;&nbsp; Internal management report &nbsp;&middot;&nbsp; {{ now()->format('Y') }}
        </div>
    </div>

</body>
</html>