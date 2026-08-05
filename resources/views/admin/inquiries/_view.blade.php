<div class="space-y-6">
    <div>
        <div class="flex items-center gap-2 mb-3">
            <span class="text-sm font-semibold text-gray-900">Guest Details</span>
            <span class="font-mono text-xs text-gray-400" x-text="view.reference_code"></span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Name</span>
                <p class="mt-1 text-sm font-medium text-gray-900" x-text="view.name || '—'"></p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Email</span>
                <p class="mt-1 text-sm text-gray-700" x-text="view.email || '—'"></p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Phone</span>
                <p class="mt-1 text-sm text-gray-700" x-text="view.phone || '—'"></p>
            </div>
        </div>
    </div>

    <template x-if="view.guest">
        <div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-semibold text-gray-900">Guest Profile</span>
                <a x-bind:href="'/admin/guests/' + view.guest.id" class="text-xs font-medium text-teal-600 hover:text-teal-700">View Profile</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Name</span>
                    <p class="mt-1 text-sm font-medium text-gray-900" x-text="view.guest.name || '—'"></p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Email</span>
                    <p class="mt-1 text-sm text-gray-700" x-text="view.guest.email || '—'"></p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Phone</span>
                    <p class="mt-1 text-sm text-gray-700" x-text="view.guest.phone || '—'"></p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Total Stays</span>
                    <p class="mt-1 text-sm font-medium text-gray-900" x-text="view.guest.total_stays"></p>
                </div>
            </div>
        </div>
    </template>

    <div>
        <span class="text-sm font-semibold text-gray-900">Booking Details</span>
        <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Type</span>
                <p class="mt-1">
                    <span class="inline-flex items-center rounded-md font-medium ring-1 ring-inset px-1.5 py-0.5 text-xs" :class="typeBadgeClass(view.booking_type)" x-text="view.booking_type_label || 'Inquiry'"></span>
                </p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Check In</span>
                <p class="mt-1 text-sm text-gray-900" x-text="view.check_in_display || '—'"></p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Check Out</span>
                <p class="mt-1 text-sm text-gray-900" x-text="view.check_out_display || '—'"></p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Pax</span>
                <p class="mt-1 text-sm text-gray-900" x-text="view.pax || '—'"></p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Cottage</span>
                <p class="mt-1 text-sm text-gray-900" x-text="view.cottage_name || 'N/A'"></p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Total Amount</span>
                <p class="mt-1 text-sm font-semibold text-gray-900" x-text="view.total_amount !== null && view.total_amount !== undefined ? '₱ ' + view.total_amount : '—'"></p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Payment</span>
                <p class="mt-1">
                    <span class="inline-flex items-center rounded-md font-medium ring-1 ring-inset px-1.5 py-0.5 text-xs" :class="paymentBadgeClass(view.payment_key)" x-text="view.payment_label"></span>
                </p>
                <p class="mt-1 text-xs text-gray-500" x-text="view.payment_detail || ''"></p>
            </div>
            <div>
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium">Status</span>
                <p class="mt-1">
                    <span class="inline-flex items-center rounded-md font-medium ring-1 ring-inset px-1.5 py-0.5 text-xs" :class="statusBadgeClass(view.status)" x-text="statusLabel(view.status)"></span>
                </p>
            </div>
        </div>
    </div>

    <template x-if="view.message">
        <div>
            <span class="text-sm font-semibold text-gray-900">Message</span>
            <p class="mt-2 text-sm text-gray-700 whitespace-pre-wrap" x-text="view.message"></p>
        </div>
    </template>

    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
        <span class="text-xs text-gray-400">Submitted <span x-text="view.created_at"></span></span>
    </div>
</div>
