@extends('admin.layouts.app')

@section('title', 'Edit Inquiry: ' . $inquiry->reference_code)
@section('header', 'Edit Inquiry')
@section('description', 'Reference: ' . $inquiry->reference_code)

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.inquiries.index') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Inquiries</a>
        <span>/</span>
        <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">{{ $inquiry->reference_code }}</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">Edit</span>
    </nav>
@endsection

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.inquiries.update', $inquiry) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Guest Details</h2>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $inquiry->name) }}" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $inquiry->email) }}" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $inquiry->phone) }}" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Linked Guest</label>
                    <select name="guest_id" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                        <option value="">None</option>
                        @foreach($guests as $id => $name)
                            <option value="{{ $id }}" {{ old('guest_id', $inquiry->guest_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Booking Details</h2>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Booking Type</label>
                    <select name="booking_type" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                        <option value="">Inquiry</option>
                        <option value="day_tour" {{ old('booking_type', $inquiry->booking_type) === 'day_tour' ? 'selected' : '' }}>Day Tour</option>
                        <option value="overnight" {{ old('booking_type', $inquiry->booking_type) === 'overnight' ? 'selected' : '' }}>Overnight</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Check In</label>
                    <input type="date" name="check_in" value="{{ old('check_in', $inquiry->check_in?->format('Y-m-d')) }}" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Check Out</label>
                    <input type="date" name="check_out" value="{{ old('check_out', $inquiry->check_out?->format('Y-m-d')) }}" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Pax</label>
                    <input type="number" name="pax" value="{{ old('pax', $inquiry->pax) }}" min="1" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Total Amount (₱)</label>
                    <input type="number" step="0.01" name="total_amount" value="{{ old('total_amount', $inquiry->total_amount) }}" min="0" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Status <span class="text-red-500">*</span></label>
                    <select name="status" required class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                        <option value="pending" {{ old('status', $inquiry->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ old('status', $inquiry->status) === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="cancelled" {{ old('status', $inquiry->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="expired" {{ old('status', $inquiry->status) === 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Cottage</label>
                    <select name="cottage_id" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                        <option value="">None</option>
                        @foreach($cottages as $id => $name)
                            <option value="{{ $id }}" {{ old('cottage_id', $inquiry->cottage_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Message</h2>
            </div>
            <div class="p-5">
                <textarea name="message" rows="4" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">{{ old('message', $inquiry->message) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.inquiries.show', $inquiry) }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</a>
            <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">Update Inquiry</button>
        </div>
    </form>
</div>
@endsection