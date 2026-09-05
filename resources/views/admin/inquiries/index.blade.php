@extends('admin.layouts.app')

@php
$inquiriesData = [];
foreach ($inquiries as $inquiry) {
    $paymentKey = $inquiry->isRefunded() ? 'refunded'
        : ($inquiry->isPaid() ? 'paid'
        : ($inquiry->hasFailedPayment() ? 'failed' : 'unpaid'));

    $paymentLabel = match ($paymentKey) {
        'refunded' => 'Refunded',
        'paid' => 'Paid',
        'failed' => 'Payment Failed',
        default => 'Unpaid',
    };

    $paymentDetail = match ($paymentKey) {
        'refunded' => 'Refunded ' . formatPrice($inquiry->refund_amount ?? $inquiry->refundableAmount())
            . ($inquiry->refunded_at ? ' on ' . $inquiry->refunded_at->format('M d, Y') : ''),
        'paid' => formatPrice($inquiry->amount_paid)
            . ' · ' . $inquiry->paymentMethodLabel()
            . (($inquiry->fully_paid_at ?? $inquiry->deposit_paid_at) ? ' · ' . ($inquiry->fully_paid_at ?? $inquiry->deposit_paid_at)->format('M d, Y') : ''),
        'failed' => $inquiry->payment_failed_at
            ? 'Last attempt failed ' . $inquiry->payment_failed_at->format('M d, Y \a\t h:i A')
            : 'Last payment attempt failed',
        default => '',
    };

    $inquiriesData[] = [
        'id' => $inquiry->id,
        'reference_code' => $inquiry->reference_code,
        'name' => $inquiry->name,
        'email' => $inquiry->email,
        'phone' => $inquiry->phone,
        'guest_id' => $inquiry->guest_id,
        'booking_type' => $inquiry->booking_type,
        'check_in' => $inquiry->check_in?->format('Y-m-d'),
        'check_out' => $inquiry->check_out?->format('Y-m-d'),
        'pax' => $inquiry->pax,
        'total_amount' => $inquiry->total_amount,
        'deposit_amount' => $inquiry->deposit_amount,
        'cottage_id' => $inquiry->cottage_id,
        'status' => $inquiry->status,
        'message' => $inquiry->message,
        'booking_type_label' => $inquiry->booking_type ? ucfirst(str_replace('_', ' ', $inquiry->booking_type)) : null,
        'check_in_display' => $inquiry->check_in?->format('M d, Y'),
        'check_out_display' => $inquiry->check_out?->format('M d, Y'),
        'cottage_name' => $inquiry->cottage?->name,
        'guest' => $inquiry->guest ? [
            'id' => $inquiry->guest->id,
            'name' => $inquiry->guest->name,
            'email' => $inquiry->guest->email,
            'phone' => $inquiry->guest->phone,
            'total_stays' => $inquiry->guest->total_stays,
        ] : null,
        'payment_key' => $paymentKey,
        'payment_label' => $paymentLabel,
        'payment_detail' => $paymentDetail,
        'created_at' => $inquiry->created_at->format('M d, Y h:i A'),
    ];
}

$showEditModal = $errors->hasAny(['name', 'email', 'phone', 'guest_id', 'booking_type', 'check_in', 'check_out', 'pax', 'total_amount', 'deposit_amount', 'cottage_id', 'status', 'message']);
$editingId = old('_editing', 0);
$editingData = null;
if ($editingId) {
    foreach ($inquiriesData as $data) {
        if ((int) $data['id'] === (int) $editingId) {
            $editingData = $data;
            break;
        }
    }
}
@endphp

@section('title', 'Inquiries')
@section('header', 'Inquiries')
@section('description', 'Manage bookings and inquiries')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">Inquiries</span>
    </nav>
@endsection

@section('content')
<div x-data="inquiryModal()" class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center gap-3 dark:border-slate-700">
            <form method="GET" class="flex flex-wrap gap-3 flex-1">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, ref #..." class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <select name="status" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
                <select name="booking_type" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All Types</option>
                    <option value="day_tour" {{ request('booking_type') === 'day_tour' ? 'selected' : '' }}>Day Tour</option>
                    <option value="overnight" {{ request('booking_type') === 'overnight' ? 'selected' : '' }}>Overnight</option>
                </select>
                <select name="source" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All Sources</option>
                    <option value="booking" {{ request('source') === 'booking' ? 'selected' : '' }}>Booking</option>
                    <option value="walk-in" {{ request('source') === 'walk-in' ? 'selected' : '' }}>Walk-In</option>
                    <option value="website" {{ request('source') === 'website' ? 'selected' : '' }}>Website</option>
                </select>
                <select name="cottage_id" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All Cottages</option>
                    @foreach($cottages as $id => $name)
                        <option value="{{ $id }}" {{ request('cottage_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors">Filter</button>
                @if(request()->anyFilled(['search', 'status', 'booking_type', 'source', 'cottage_id']))
                    <a href="{{ route('admin.inquiries.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Clear</a>
                @endif
            </form>
            <button type="button" @@click="openCreate()" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-lg hover:bg-teal-800 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Inquiry
            </button>
        </div>

        @if($inquiries->isEmpty())
            @include('components.admin.empty-state', ['title' => 'No inquiries', 'message' => 'Inquiries from guests will appear here.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
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
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @foreach($inquiries as $inquiry)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                            <td class="px-5 py-3 text-gray-500 font-medium dark:text-slate-400">{{ $inquiry->reference_code }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $inquiry->name }}</td>
                            <td class="px-5 py-3 text-gray-500 hidden sm:table-cell dark:text-slate-400">{{ $inquiry->email }}</td>
                            <td class="px-5 py-3">@include('components.admin.badge', ['type' => 'primary', 'slot' => $inquiry->cottage?->name ?? 'N/A'])</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-slate-300">{{ $inquiry->check_in?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-slate-300">{{ $inquiry->check_out?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-center hidden md:table-cell">{{ $inquiry->pax ?? '—' }}</td>
                            <td class="px-5 py-3 hidden md:table-cell">@include('components.admin.badge', ['type' => $inquiry->booking_type === 'day_tour' ? 'info' : ($inquiry->booking_type === 'overnight' ? 'warning' : 'gray'), 'slot' => $inquiry->booking_type ? ucfirst(str_replace('_', ' ', $inquiry->booking_type)) : '—'])</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1.5">
                                    @include('components.admin.badge', ['type' => $inquiry->status === 'confirmed' ? 'success' : ($inquiry->status === 'cancelled' ? 'danger' : ($inquiry->status === 'expired' ? 'gray' : 'warning')), 'slot' => ucfirst($inquiry->status)])
                                    @if($inquiry->isRefunded())
                                        @include('components.admin.badge', ['type' => 'danger', 'slot' => 'Refunded'])
                                    @elseif($inquiry->isPaid())
                                        @include('components.admin.badge', ['type' => 'primary', 'slot' => 'Paid'])
                                    @elseif($inquiry->hasFailedPayment())
                                        @include('components.admin.badge', ['type' => 'danger', 'slot' => 'Payment Failed'])
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @@click="openView({{ $inquiry->id }})" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-blue-400 dark:hover:bg-blue-500/10" aria-label="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </button>
                                    <button type="button" @@click="openEdit({{ $inquiry->id }})" class="p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    @if($inquiry->status === 'pending')
                                    <button type="button" class="p-1.5 text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors dark:hover:bg-emerald-500/10" aria-label="Confirm"
                                        @@click="$dispatch('open-confirm-confirm', { url: '{{ route('admin.inquiries.confirm', $inquiry) }}' })">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    <button type="button" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:hover:bg-red-500/10" aria-label="Cancel Booking"
                                        @@click="$dispatch('open-confirm-cancel', { url: '{{ route('admin.inquiries.cancel', $inquiry) }}' })">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    @endif
                                    <button type="button" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-500/10" aria-label="Delete"
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

            <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-700">
                @include('components.admin.pagination', ['paginator' => $inquiries])
            </div>
        @endif
    </div>

    {{-- View Modal --}}
    <x-admin.modal name="inquiry-view" size="xl">
        @include('admin.inquiries._view')

        <div class="flex flex-wrap items-center justify-end gap-2 pt-5 mt-6 border-t border-gray-100 dark:border-slate-700">
            <template x-if="view.status === 'pending'">
                <div class="flex items-center gap-2">
                    <button type="button"
                        @@click="$dispatch('open-confirm-confirm', { url: '/admin/inquiries/' + view.id + '/confirm' })"
                        class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">Confirm Booking</button>
                    <button type="button"
                        @@click="$dispatch('open-confirm-cancel', { url: '/admin/inquiries/' + view.id + '/cancel' })"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors shadow-sm">Cancel Booking</button>
                </div>
            </template>
            <template x-if="view.status === 'confirmed' && view.payment_key === 'unpaid'">
                <button type="button"
                    @@click="$dispatch('open-confirm-mark-paid', { url: '/admin/inquiries/' + view.id + '/mark-paid' })"
                    class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">Mark as Paid</button>
            </template>
            <template x-if="view.payment_key === 'paid'">
                <button type="button"
                    @@click="$dispatch('open-confirm-refund', { url: '/admin/inquiries/' + view.id + '/refund' })"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors shadow-sm">Refund</button>
            </template>
            <button type="button"
                @@click="openEdit(view.id)"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                Edit
            </button>
        </div>
    </x-admin.modal>

    {{-- Edit/Form Modal --}}
    <x-admin.modal name="inquiry-form" size="lg">
        <form method="POST" :action="formAction">
            @csrf
            <input type="hidden" name="_method" :value="formMethod">
            <input type="hidden" name="_editing" :value="editingId || ''">

            @include('admin.inquiries._form')

            <div class="flex items-center justify-end gap-3 pt-5 mt-6 border-t border-gray-100 dark:border-slate-700">
                <button type="button" @@click="window.dispatchEvent(new CustomEvent('close-modal-inquiry-form'))" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">
                    <span x-text="isEditing ? 'Update Inquiry' : 'Add Inquiry'"></span>
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>

@include('components.admin.confirm-dialog', ['name' => 'delete', 'title' => 'Delete Inquiry?', 'message' => 'Are you sure you want to delete this inquiry? This action cannot be undone.'])
@include('components.admin.confirm-dialog', ['name' => 'confirm', 'title' => 'Confirm Booking?', 'message' => 'Confirm this booking? This will create date blocks and send a confirmation email to the guest.', 'confirmText' => 'Confirm Booking', 'confirmClass' => 'bg-emerald-600 hover:bg-emerald-700 text-white'])
@include('components.admin.confirm-dialog', ['name' => 'cancel', 'title' => 'Cancel Booking?', 'message' => 'Cancel this booking? This will remove date blocks and send a cancellation email to the guest.', 'confirmText' => 'Cancel Booking', 'confirmClass' => 'bg-red-600 hover:bg-red-700 text-white'])
@include('components.admin.confirm-dialog', ['name' => 'mark-paid', 'title' => 'Mark as Paid?', 'message' => 'Mark this booking as paid (e.g. bank transfer or cash on site)?', 'confirmText' => 'Mark as Paid', 'confirmClass' => 'bg-emerald-600 hover:bg-emerald-700 text-white'])
@include('components.admin.confirm-dialog', ['name' => 'refund', 'title' => 'Refund Payment?', 'message' => 'Refund the collected amount via PayMongo and cancel this booking? The guest will be notified by email.', 'confirmText' => 'Refund & Cancel', 'confirmClass' => 'bg-red-600 hover:bg-red-700 text-white'])
@endsection

<script>
window.inquiryModal = function() {
    return {
        inquiries: @js($inquiriesData),
        cottageRates: @js($cottageRates),
        view: {},
        isEditing: false,
        editingId: null,
        lastAutoTotal: null,
        form: {
            name: '',
            email: '',
            phone: '',
            guest_id: '',
            booking_type: 'day_tour',
            check_in: '',
            check_out: '',
            pax: '',
            total_amount: '',
            cottage_id: '',
            status: 'pending',
            message: '',
        },
        formAction: '',
        formMethod: 'PUT',

        isPeakDate(r, dateStr) {
            if (!r.peak_start || !r.peak_end || !dateStr) return false;
            const md = dateStr.slice(5);
            const start = r.peak_start;
            const end = r.peak_end;
            if (start <= end) return md >= start && md <= end;
            return md >= start || md <= end;
        },

        rateFor(r, type, dateStr) {
            if (this.isPeakDate(r, dateStr)) {
                const peak = type === 'day_tour' ? r.peak_day_tour : r.peak_overnight;
                if (peak && peak > 0) return peak;
            }
            return type === 'day_tour' ? r.day_tour : r.overnight;
        },

        addDays(dateStr, days) {
            const d = new Date(dateStr + 'T00:00:00');
            d.setDate(d.getDate() + days);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        },

        get suggestedTotal() {
            if (!this.form.cottage_id || !this.cottageRates[this.form.cottage_id]) {
                return null;
            }
            const r = this.cottageRates[this.form.cottage_id];
            if (this.form.booking_type === 'day_tour') {
                return Number(this.rateFor(r, 'day_tour', this.form.check_in));
            }
            if (this.form.booking_type === 'overnight') {
                if (!this.form.check_in || !this.form.check_out) {
                    return null;
                }
                const a = new Date(this.form.check_in + 'T00:00:00');
                const b = new Date(this.form.check_out + 'T00:00:00');
                const nights = Math.max(1, Math.round((b - a) / (1000 * 60 * 60 * 24)));
                let total = 0;
                for (let i = 0; i < nights; i++) {
                    const d = this.addDays(this.form.check_in, i);
                    total += Number(this.rateFor(r, 'overnight', d));
                }
                return total;
            }
            return null;
        },

        applySuggestedTotal() {
            const suggested = this.suggestedTotal;
            const current = this.form.total_amount === null || this.form.total_amount === undefined ? '' : String(this.form.total_amount);
            const last = this.lastAutoTotal === null ? '' : String(this.lastAutoTotal);

            if (suggested !== null && (current === '' || current === last)) {
                this.form.total_amount = suggested;
                this.lastAutoTotal = suggested;
            } else if (suggested === null && current === last) {
                this.form.total_amount = '';
                this.lastAutoTotal = null;
            }
        },

        openView(id) {
            const inquiry = this.inquiries.find(i => i.id === id);
            if (!inquiry) return;
            this.view = inquiry;
            window.dispatchEvent(new CustomEvent('open-modal-inquiry-view', { detail: { title: 'Inquiry ' + inquiry.reference_code } }));
        },

        openCreate() {
            this.isEditing = false;
            this.editingId = null;
            this.lastAutoTotal = null;
            this.form = {
                name: '',
                email: '',
                phone: '',
                guest_id: '',
                booking_type: 'day_tour',
                check_in: '',
                check_out: '',
                pax: '',
                total_amount: '',
                deposit_amount: '',
                cottage_id: '',
                status: 'pending',
                message: '',
            };
            this.formAction = '{{ route('admin.inquiries.store') }}';
            this.formMethod = 'POST';
            window.dispatchEvent(new CustomEvent('close-modal-inquiry-view'));
            window.dispatchEvent(new CustomEvent('open-modal-inquiry-form', { detail: { title: 'Add Inquiry' } }));
        },

        openEdit(id) {
            const inquiry = this.inquiries.find(i => i.id === id);
            if (!inquiry) return;
            this.isEditing = true;
            this.editingId = inquiry.id;
            this.lastAutoTotal = null;
            this.form = {
                name: inquiry.name,
                email: inquiry.email,
                phone: inquiry.phone || '',
                guest_id: inquiry.guest_id || '',
                booking_type: inquiry.booking_type || '',
                check_in: inquiry.check_in || '',
                check_out: inquiry.check_out || '',
                pax: inquiry.pax || '',
                total_amount: inquiry.total_amount ?? '',
                deposit_amount: inquiry.deposit_amount ?? '',
                cottage_id: inquiry.cottage_id || '',
                status: inquiry.status,
                message: inquiry.message || '',
            };
            this.formAction = '/admin/inquiries/' + inquiry.id;
            this.formMethod = 'PUT';
            window.dispatchEvent(new CustomEvent('close-modal-inquiry-view'));
            window.dispatchEvent(new CustomEvent('open-modal-inquiry-form', { detail: { title: 'Edit Inquiry ' + inquiry.reference_code } }));
        },

        statusLabel(status) {
            return status ? status.charAt(0).toUpperCase() + status.slice(1) : '—';
        },

        statusBadgeClass(status) {
            return status === 'confirmed' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30'
                : status === 'cancelled' ? 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30'
                : status === 'expired' ? 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-slate-500/10 dark:text-slate-300 dark:ring-slate-400/30'
                : 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30';
        },

        typeBadgeClass(type) {
            return type === 'day_tour' ? 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30'
                : type === 'overnight' ? 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30'
                : 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-slate-500/10 dark:text-slate-300 dark:ring-slate-400/30';
        },

        paymentBadgeClass(key) {
            return key === 'paid' ? 'bg-teal-50 text-teal-700 ring-teal-600/20 dark:bg-teal-500/10 dark:text-teal-300 dark:ring-teal-400/30'
                : key === 'refunded' || key === 'failed' ? 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/30'
                : 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-slate-500/10 dark:text-slate-300 dark:ring-slate-400/30';
        },

        init() {
            ['form.cottage_id', 'form.booking_type', 'form.check_in', 'form.check_out'].forEach(path => {
                this.$watch(path, () => this.applySuggestedTotal());
            });

            const showModal = @js($showEditModal);
            const editingId = @js($editingData ? $editingData['id'] : 0);
            const oldName = @js(old('name', ''));
            const oldEmail = @js(old('email', ''));
            const oldPhone = @js(old('phone', ''));
            const oldGuestId = @js(old('guest_id', ''));
            const oldBookingType = @js(old('booking_type', ''));
            const oldCheckIn = @js(old('check_in', ''));
            const oldCheckOut = @js(old('check_out', ''));
            const oldPax = @js(old('pax', ''));
            const oldTotal = @js(old('total_amount', ''));
            const oldDeposit = @js(old('deposit_amount', ''));
            const oldCottageId = @js(old('cottage_id', ''));
            const oldStatus = @js(old('status', ''));
            const oldMessage = @js(old('message', ''));

            if (showModal) {
                if (editingId) {
                    this.openEdit(Number(editingId));
                } else {
                    this.openCreate();
                }
                this.$nextTick(() => {
                    if (oldName) this.form.name = oldName;
                    if (oldEmail) this.form.email = oldEmail;
                    if (oldPhone) this.form.phone = oldPhone;
                    if (oldGuestId) this.form.guest_id = oldGuestId;
                    if (oldBookingType) this.form.booking_type = oldBookingType;
                    if (oldCheckIn) this.form.check_in = oldCheckIn;
                    if (oldCheckOut) this.form.check_out = oldCheckOut;
                    if (oldPax) this.form.pax = oldPax;
                    if (oldTotal !== null && oldTotal !== '') this.form.total_amount = oldTotal;
                    if (oldDeposit !== null && oldDeposit !== '') this.form.deposit_amount = oldDeposit;
                    if (oldCottageId) this.form.cottage_id = oldCottageId;
                    if (oldStatus) this.form.status = oldStatus;
                    this.form.message = oldMessage;
                });
            }
        },
    };
}
</script>
