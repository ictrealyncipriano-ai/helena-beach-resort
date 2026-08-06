<div class="space-y-5">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Key <span class="text-red-500">*</span></label>
        <input type="text" name="key" x-model="form.key" required maxlength="255"
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('key') border-red-300 dark:border-red-500 @enderror">
        @error('key') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Value</label>
        <textarea name="value" rows="4" x-model="form.value"
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('value') border-red-300 dark:border-red-500 @enderror"></textarea>
        @error('value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Type <span class="text-red-500">*</span></label>
        <select name="type" x-model="form.type" required
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('type') border-red-300 dark:border-red-500 @enderror">
            <option value="text">Text</option>
            <option value="textarea">Textarea</option>
            <option value="image">Image</option>
        </select>
        @error('type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
</div>
