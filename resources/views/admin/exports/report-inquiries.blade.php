@extends('admin.exports.report-layout')

@section('report-content')
    <div class="summary">
        <div class="stat">
            <div class="label">Total Inquiries</div>
            <div class="value">{{ $totalCount }}</div>
        </div>
        <div class="stat">
            <div class="label">Total Amount</div>
            <div class="value">₱{{ number_format($totalAmount, 2) }}</div>
        </div>
        <div class="stat">
            <div class="label">Total Paid</div>
            <div class="value">₱{{ number_format($totalPaid, 2) }}</div>
        </div>
        <div class="stat">
            <div class="label">By Status</div>
            <div class="value small">
                @php
                    $activeStatuses = collect(['pending', 'confirmed', 'cancelled', 'expired'])
                        ->filter(fn ($s) => ($statusCounts[$s] ?? 0) > 0);
                @endphp
                @foreach($activeStatuses as $index => $s)
                    {{ ucfirst($s) }}: {{ $statusCounts[$s] }}@if(! $loop->last)<br>@endif
                @endforeach
                @if($activeStatuses->isEmpty())—@endif
            </div>
        </div>
    </div>

    @if($rows->isEmpty())
        <div class="empty">No inquiries match the selected filters.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Guest</th>
                    <th>Type</th>
                    <th>Cottage</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th class="c">Pax</th>
                    <th class="r">Total</th>
                    <th class="r">Paid</th>
                    <th>Payment</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $inquiry)
                    <tr>
                        <td class="mono">{{ $inquiry->reference_code }}</td>
                        <td>
                            {{ $inquiry->name }}
                            <br><span class="sub">{{ $inquiry->email }}</span>
                            @if($inquiry->phone)
                                <br><span class="sub">{{ $inquiry->phone }}</span>
                            @endif
                        </td>
                        <td>{{ $inquiry->booking_type ? str_replace('_', ' ', ucfirst($inquiry->booking_type)) : '—' }}</td>
                        <td>{{ $inquiry->cottage?->name ?? '—' }}</td>
                        <td><span class="badge badge-{{ $inquiry->status }}">{{ ucfirst($inquiry->status) }}</span></td>
                        <td>{{ $inquiry->check_in?->format('M d, Y') ?? '—' }}</td>
                        <td>{{ $inquiry->check_out?->format('M d, Y') ?? '—' }}</td>
                        <td class="c">{{ $inquiry->pax ?? '—' }}</td>
                        <td class="r">₱{{ number_format($inquiry->total_amount ?? 0, 2) }}</td>
                        <td class="r">₱{{ number_format($inquiry->paid_amount ?? 0, 2) }}</td>
                        <td>{{ $inquiry->paymentMethodLabel() }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8">Totals ({{ $totalCount }} inquiries)</td>
                    <td class="r">₱{{ number_format($totalAmount, 2) }}</td>
                    <td class="r">₱{{ number_format($totalPaid, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection