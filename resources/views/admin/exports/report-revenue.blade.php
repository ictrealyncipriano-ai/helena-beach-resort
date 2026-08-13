@extends('admin.exports.report-layout')

@section('report-content')
    <div class="summary">
        <div class="stat">
            <div class="label">Paid Bookings</div>
            <div class="value">{{ $grandBookings }}</div>
        </div>
        <div class="stat">
            <div class="label">Total Revenue</div>
            <div class="value">₱{{ number_format($grandTotal, 2) }}</div>
        </div>
    </div>

    @if($rows->isEmpty())
        <div class="empty">No paid bookings match the selected period.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Cottage</th>
                    <th class="r">Bookings</th>
                    <th class="r">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row->period }}</td>
                        <td>{{ $row->cottage_name }}</td>
                        <td class="r">{{ $row->bookings }}</td>
                        <td class="r">₱{{ number_format($row->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Grand Total</td>
                    <td class="r">{{ $grandBookings }}</td>
                    <td class="r">₱{{ number_format($grandTotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection