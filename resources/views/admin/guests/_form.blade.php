<div class="space-y-5">
    <div>
        <h2 class="text-sm font-semibold text-gray-900 mb-3 dark:text-white">Guest Details</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="name-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Name <span class="text-red-600">*</span></label>
                <input type="text" name="name" id="name-field" x-model="form.name" required maxlength="255"
                    @error('name') aria-invalid="true" aria-describedby="name-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('name') border-red-300 dark:border-red-500 @enderror">
                @error('name') <p id="name-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Email <span class="text-red-600">*</span></label>
                <input type="email" name="email" id="email-field" x-model="form.email" required maxlength="255"
                    @error('email') aria-invalid="true" aria-describedby="email-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('email') border-red-300 dark:border-red-500 @enderror">
                @error('email') <p id="email-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="phone-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Phone</label>
                <input type="text" name="phone" id="phone-field" x-model="form.phone" maxlength="20"
                    @error('phone') aria-invalid="true" aria-describedby="phone-field-error" @enderror
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('phone') border-red-300 dark:border-red-500 @enderror">
                @error('phone') <p id="phone-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div>
        <label for="notes-field" class="block text-sm font-medium text-gray-700 mb-1.5 dark:text-slate-300">Notes</label>
        <textarea name="notes" id="notes-field" rows="4" x-model="form.notes"
            @error('notes') aria-invalid="true" aria-describedby="notes-field-error" @enderror
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('notes') border-red-300 dark:border-red-500 @enderror"></textarea>
        @error('notes') <p id="notes-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
