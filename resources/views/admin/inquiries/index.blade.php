@extends('admin.layouts.app')

@section('title', 'Inquiries')
@section('header', 'Inquiries')
@section('description', 'Manage bookings and inquiries')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-600 transition-colors">Dashboard</a>
        <span>/</span>
        <span class="text-gray-700 font-medium">Inquiries</span>
    </nav>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="p-4 sm:p-5 border-b border-gray-100">
        <form method="GET" class="flex flex-wrap gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, ref #..." class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400">
            </div>
            <select name="status" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 bg-white">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
            <select name="booking_type" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 bg-white">
                <option value="">All Types</option>
                <option value="day_tour" {{ request('booking_type') === 'day_tour' ? 'selected' : '' }}>Day Tour</option>
                <option value="overnight" {{ request('booking_type') === 'overnight' ? 'selected' : '' }}>Overnight</option>
            </select>
            <select name="source" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 bg-white">
                <option value="">All Sources</option>
                <option value="booking" {{ request('source') === 'booking' ? 'selected' : '' }}>Booking</option>
                <option value="website" {{ request('source') === 'website' ? 'selected' : '' }}>Website</option>
            </select>
            <select name="cottage_id" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 bg-white">
                <option value="">All Cottages</option>
                @foreach($cottages as $id => $name)
                    <option value="{{ $id }}" {{ request('cottage_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors">Filter</button>
            @if(request()->anyFilled(['search', 'status', 'booking_type', 'source', 'cottage_id']))
                <a href="{{ route('admin.inquiries.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Clear</a>
            @endif
        </form>
    </div>

    @if($inquiries->isEmpty())
        @include('admin.components.empty-state', ['title' => 'No inquiries', 'message' => 'Inquiries from guests will appear here.'])
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="text-left px-5 py-3 font-medium">Ref #</th>
                        <th class="text-left px-5 py-3 font-medium">Name</th>
                        <th class="text-left px-5 py-3 font-medium hidden sm:table-cell">Email</th>
                        <th class="text-left px-5 py-3 font-medium">Cottage</th>
                        <th class="text-left px-5 py-3 font-medium">Check In</th>
                        <th class="text-left px-5 py-3 font-medium">Check Out</th>
                        <th class="text-center px-5 py-3 font-medium hidden md:table-cell">Pax</th>
                        <th class="text-left px-5 py-3 font-medium hidden md:table-cell">Type</th>
                        <th class="text-left px-5 py-3 font-medium">Status</th>
                        <th class="text-right px-5 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($inquiries as $inquiry)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3 text-gray-500 font-medium">{{ $inquiry->reference_code }}</td>
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $inquiry->name }}</td>
                        <td class="px-5 py-3 text-gray-500 hidden sm:table-cell">{{ $inquiry->email }}</td>
                        <td class="px-5 py-3">@include('admin.components.badge', ['type' => 'primary', 'slot' => $inquiry->cottage?->name ?? 'N/A'])</td>
                        <td class="px-5 py-3 text-gray-600">{{ $inquiry->check_in?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $inquiry->check_out?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-5 py-3 text-center hidden md:table-cell">{{ $inquiry->pax ?? '—' }}</td>
                        <td class="px-5 py-3 hidden md:table-cell">@include('admin.components.badge', ['type' => $inquiry->booking_type === 'day_tour' ? 'info' : ($inquiry->booking_type === 'overnight' ? 'warning' : 'gray'), 'slot' => $inquiry->booking_type ? ucfirst(str_replace('_', ' ', $inquiry->booking_type)) : '—'])</td>
                        <td class="px-5 py-3">@include('admin.components.badge', ['type' => $inquiry->status === 'confirmed' ? 'success' : ($inquiry->status === 'cancelled' ? 'danger' : ($inquiry->status === 'expired' ? 'gray' : 'warning')), 'slot' => ucfirst($inquiry->status)])</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </a>
                                <a href="{{ route('admin.inquiries.edit', $inquiry) }}" class="p-1.5 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                </a>
                                @if($inquiry->status === 'pending')
                                <form action="{{ route('admin.inquiries.confirm', $inquiry) }}" method="POST" class="inline" onsubmit="return confirm('Confirm this booking? This will create date blocks and send a confirmation email.')">
                                    @csrf
                                    <button type="submit" class="p-1.5 text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors" title="Confirm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    <button type="submit" formaction="{{ route('admin.inquiries.cancel', $inquiry) }}" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Cancel Booking"
                                        onclick="return confirm('Cancel this booking? This will remove date blocks and send a cancellation email to the guest.')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </form>
                                @endif
                                <button type="button" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete"
                                    @@click="$dispatch('open-confirm-delete', { url: '{{ route('admin.inquiries.destroy', $inquiry) }}', method: 'DELETE' })">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-gray-100">
            @include('admin.components.pagination', ['paginator' => $inquiries])
        </div>
    @endif
</div>

@include('admin.components.confirm-dialog', ['name' => 'delete', 'title' => 'Delete Inquiry?', 'message' => 'Are you sure you want to delete this inquiry? This action cannot be undone.'])
@endsection