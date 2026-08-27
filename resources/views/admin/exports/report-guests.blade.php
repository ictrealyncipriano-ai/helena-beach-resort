@extends('admin.exports.report-layout')

@section('report-content')
    <div class="summary">
        <div class="stat">
            <div class="label">Guests</div>
            <div class="value">{{ $totalCount }}</div>
        </div>
        <div class="stat">
            <div class="label">Total Stays</div>
            <div class="value">{{ $totalStays }}</div>
        </div>
        <div class="stat">
            <div class="label">Total Revenue</div>
            <div class="value">{{ formatPrice($totalRevenue) }}</div>
        </div>
    </div>

    @if($rows->isEmpty())
        <div class="empty">No guests found.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th class="r">Stays</th>
                    <th>Last Stay</th>
                    <th class="r">Inquiries</th>
                    <th class="r">Paid</th>
                    <th class="r">Refunded</th>
                    <th class="r">Failed</th>
                    <th class="r">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $guest)
                    <tr>
                        <td>{{ $guest->name }}</td>
                        <td>{{ $guest->email }}</td>
                        <td>{{ $guest->phone }}</td>
                        <td class="r">{{ $guest->total_stays }}</td>
                        <td>{{ $guest->last_stay_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="r">{{ $guest->inquiries_count }}</td>
                        <td class="r">{{ $guest->paid_count }}</td>
                        <td class="r">{{ $guest->refunded_count }}</td>
                        <td class="r">{{ $guest->failed_count }}</td>
                        <td class="r">{{ formatPrice($guest->paid_amount ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Totals ({{ $totalCount }} guests)</td>
                    <td class="r">{{ $totalStays }}</td>
                    <td></td>
                    <td class="r">{{ $rows->sum('inquiries_count') }}</td>
                    <td class="r">{{ $rows->sum('paid_count') }}</td>
                    <td class="r">{{ $rows->sum('refunded_count') }}</td>
                    <td class="r">{{ $rows->sum('failed_count') }}</td>
                    <td class="r">{{ formatPrice($totalRevenue) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection