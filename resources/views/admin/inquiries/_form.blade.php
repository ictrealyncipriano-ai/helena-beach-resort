<div class="space-y-6">
    <div>
        <h2 class="text-sm font-semibold text-gray-900 mb-3 dark:text-white">Guest Details</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="name-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Name <span class="text-red-600">*</span></label>
                <input type="text" name="name" id="name-field" x-model="form.name" required maxlength="255"
                    @error('name') aria-invalid="true" aria-describedby="name-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('name') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                @error('name') <p id="name-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Email <span class="text-red-600">*</span></label>
                <input type="email" name="email" id="email-field" x-model="form.email" required maxlength="255"
                    @error('email') aria-invalid="true" aria-describedby="email-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('email') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                @error('email') <p id="email-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Phone</label>
                <input type="text" name="phone" id="phone-field" x-model="form.phone" maxlength="255"
                    @error('phone') aria-invalid="true" aria-describedby="phone-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('phone') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                @error('phone') <p id="phone-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="guest-id-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Linked guest</label>
                <select name="guest_id" id="guest-id-field" x-model="form.guest_id"
                    @error('guest_id') aria-invalid="true" aria-describedby="guest-id-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all bg-white @error('guest_id') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                    <option value="">None</option>
                    @foreach($guests as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                @error('guest_id') <p id="guest-id-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div>
        <h2 class="text-sm font-semibold text-gray-900 mb-3 dark:text-white">Booking Details</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="booking-type-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Booking Type</label>
                <select name="booking_type" id="booking-type-field" x-model="form.booking_type"
                    @error('booking_type') aria-invalid="true" aria-describedby="booking-type-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all bg-white @error('booking_type') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                    <option value="">Inquiry</option>
                    <option value="day_tour">Day Tour</option>
                    <option value="overnight">Overnight</option>
                </select>
                @error('booking_type') <p id="booking-type-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="check-in-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Check In</label>
                <input type="date" name="check_in" id="check-in-field" x-model="form.check_in"
                    @error('check_in') aria-invalid="true" aria-describedby="check-in-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('check_in') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                @error('check_in') <p id="check-in-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="check-out-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Check Out</label>
                <input type="date" name="check_out" id="check-out-field" x-model="form.check_out"
                    @error('check_out') aria-invalid="true" aria-describedby="check-out-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('check_out') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                @error('check_out') <p id="check-out-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="pax-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Pax</label>
                <input type="number" name="pax" id="pax-field" x-model="form.pax" min="1"
                    @error('pax') aria-invalid="true" aria-describedby="pax-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('pax') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                @error('pax') <p id="pax-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="total-amount-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Total Amount (₱)</label>
                <input type="number" step="0.01" name="total_amount" id="total-amount-field" x-model="form.total_amount" min="0"
                    @error('total_amount') aria-invalid="true" aria-describedby="total-amount-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('total_amount') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                @error('total_amount') <p id="total-amount-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <template x-if="suggestedTotal !== null">
                    <p class="mt-1 text-xs text-teal-700 dark:text-teal-300">Suggested amount: ₱<span x-text="suggestedTotal.toLocaleString()"></span> — auto-calculated. Edit to override.</p>
                </template>
                <template x-else-if="form.booking_type === 'overnight' && form.cottage_id && !(form.check_in && form.check_out)">
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Select check-in and check-out to calculate the overnight rate.</p>
                </template>
            </div>
            <div>
                <label for="deposit-amount-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Deposit (₱)</label>
                <input type="number" step="0.01" name="deposit_amount" id="deposit-amount-field" x-model="form.deposit_amount" min="0"
                    @error('deposit_amount') aria-invalid="true" aria-describedby="deposit-amount-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('deposit_amount') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                @error('deposit_amount') <p id="deposit-amount-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Guest pays this first online, then the balance.</p>
            </div>
            <div>
                <label for="status-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Status <span class="text-red-600">*</span></label>
                <select name="status" id="status-field" x-model="form.status" required
                    @error('status') aria-invalid="true" aria-describedby="status-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all bg-white @error('status') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="expired">Expired</option>
                </select>
                @error('status') <p id="status-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="cottage-id-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-200">Cottage</label>
                <select name="cottage_id" id="cottage-id-field" x-model="form.cottage_id"
                    @error('cottage_id') aria-invalid="true" aria-describedby="cottage-id-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all bg-white @error('cottage_id') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
                    <option value="">None</option>
                    @foreach($cottages as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                @error('cottage_id') <p id="cottage-id-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div>
        <label for="message-field" class="block text-sm font-medium text-gray-700 mb-1.5 dark:text-slate-200">Message</label>
        <textarea name="message" id="message-field" rows="4" x-model="form.message"
            @error('message') aria-invalid="true" aria-describedby="message-field-error" @enderror
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('message') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">{{ old('message') }}</textarea>
        @error('message') <p id="message-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
