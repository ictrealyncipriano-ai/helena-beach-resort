<div class="space-y-5">
    <div>
        <h4 class="text-sm font-semibold text-gray-900 mb-3 dark:text-white">Guest Details</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" x-model="form.name" required maxlength="255"
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('name') border-red-300 dark:border-red-500 @enderror">
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" x-model="form.email" required maxlength="255"
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('email') border-red-300 dark:border-red-500 @enderror">
                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Phone</label>
                <input type="text" name="phone" x-model="form.phone" maxlength="20"
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('phone') border-red-300 dark:border-red-500 @enderror">
                @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5 dark:text-slate-300">Notes</label>
        <textarea name="notes" rows="4" x-model="form.notes"
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('notes') border-red-300 dark:border-red-500 @enderror"></textarea>
        @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
</div>
