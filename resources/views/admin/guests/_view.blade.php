<div class="space-y-6">
    <div>
        <span class="text-sm font-semibold text-gray-900 dark:text-white">Guest Information</span>
        <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="min-w-0">
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Name</span>
                <p class="mt-1 text-sm font-medium text-gray-900 break-all dark:text-white" x-text="view.name || '—'"></p>
            </div>
            <div class="min-w-0">
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Email</span>
                <p class="mt-1 text-sm text-gray-700 break-all dark:text-slate-300" x-text="view.email || '—'"></p>
            </div>
            <div class="min-w-0">
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Phone</span>
                <p class="mt-1 text-sm text-gray-700 break-all dark:text-slate-300" x-text="view.phone || '—'"></p>
            </div>
            <div class="min-w-0">
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Total Stays</span>
                <p class="mt-1 text-sm font-semibold text-gray-900 break-all dark:text-white" x-text="view.total_stays"></p>
            </div>
            <div class="min-w-0">
                <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Last Stay</span>
                <p class="mt-1 text-sm text-gray-700 break-all dark:text-slate-300" x-text="view.last_stay || '—'"></p>
            </div>
        </div>
    </div>

    <template x-if="view.notes">
        <div>
            <span class="text-sm font-semibold text-gray-900 dark:text-white">Notes</span>
            <p class="mt-2 text-sm text-gray-700 whitespace-pre-wrap dark:text-slate-300" x-text="view.notes"></p>
        </div>
    </template>

    <template x-if="view.inquiries_count > 0">
        <div>
            <span class="text-sm font-semibold text-gray-900 dark:text-white">Booking Stats</span>
            <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Paid Bookings</span>
                    <p class="mt-1 text-sm font-semibold text-emerald-600 dark:text-emerald-400" x-text="view.stats.paid_count"></p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Total Paid Amount</span>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white" x-text="formatAmount(view.stats.paid_amount)"></p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Payment Failures</span>
                    <p class="mt-1 text-sm font-semibold text-red-600 dark:text-red-400" x-text="view.stats.failed_count"></p>
                </div>
                <div>
                    <span class="text-xs text-gray-500 uppercase tracking-wider font-medium dark:text-slate-400">Refunded</span>
                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white" x-text="view.stats.refunded_count"></p>
                </div>
            </div>
        </div>
    </template>

    <div>
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-semibold text-gray-900 dark:text-white">Booking History</span>
            <span class="text-xs text-gray-500 dark:text-slate-400" x-text="view.inquiries_count + ' total'"></span>
        </div>
        <template x-if="view.inquiries && view.inquiries.length">
            <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-slate-700">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                            <th class="text-left px-4 py-3 font-medium">Ref #</th>
                            <th class="text-left px-4 py-3 font-medium">Cottage</th>
                            <th class="text-left px-4 py-3 font-medium">Check In</th>
                            <th class="text-left px-4 py-3 font-medium">Check Out</th>
                            <th class="text-left px-4 py-3 font-medium">Type</th>
                            <th class="text-left px-4 py-3 font-medium">Status</th>
                            <th class="text-left px-4 py-3 font-medium">Payment</th>
                            <th class="text-right px-4 py-3 font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        <template x-for="inquiry in view.inquiries" :key="inquiry.reference_code">
                            <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                                <td class="px-4 py-3 text-gray-500 font-medium dark:text-slate-400" x-text="inquiry.reference_code"></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-slate-300" x-text="inquiry.cottage_name"></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-slate-300" x-text="inquiry.check_in"></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-slate-300" x-text="inquiry.check_out"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-md font-medium ring-1 ring-inset px-1.5 py-0.5 text-xs" :class="typeBadgeClass(inquiry.booking_type)" x-text="inquiry.booking_type_label"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-md font-medium ring-1 ring-inset px-1.5 py-0.5 text-xs" :class="statusBadgeClass(inquiry.status)" x-text="statusLabel(inquiry.status)"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-md font-medium ring-1 ring-inset px-1.5 py-0.5 text-xs" :class="paymentBadgeClass(inquiry.payment_key)" x-text="inquiry.payment_label"></span>
                                    <template x-if="inquiry.payment_key === 'paid' && inquiry.payment_method">
                                        <p class="mt-0.5 text-[11px] text-gray-500 dark:text-slate-400" x-text="inquiry.payment_method"></p>
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-right font-medium dark:text-white" x-text="inquiry.total_amount"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
        <template x-else>
            <p class="text-sm text-gray-500 dark:text-slate-400">No bookings found for this guest.</p>
        </template>
    </div>

    <div class="pt-4 border-t border-gray-100 dark:border-slate-700 flex items-center justify-between">
        <span class="text-xs text-gray-500 dark:text-slate-400">Guest since <span x-text="view.created"></span></span>
    </div>
</div>
