<div class="space-y-5">
    <div>
        <label for="key-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Key <span class="text-red-600">*</span></label>
        <input type="text" name="key" id="key-field" x-model="form.key" required maxlength="255"
            @error('key') aria-invalid="true" aria-describedby="key-field-error" @enderror
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('key') border-red-300 dark:border-red-500 @enderror">
        @error('key') <p id="key-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="value-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Value</label>
        <textarea name="value" id="value-field" rows="4" x-model="form.value"
            @error('value') aria-invalid="true" aria-describedby="value-field-error" @enderror
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('value') border-red-300 dark:border-red-500 @enderror"></textarea>
        @error('value') <p id="value-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="type-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Type <span class="text-red-600">*</span></label>
        <select name="type" id="type-field" x-model="form.type" required
            @error('type') aria-invalid="true" aria-describedby="type-field-error" @enderror
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('type') border-red-300 dark:border-red-500 @enderror">
            <option value="text">Text</option>
            <option value="textarea">Textarea</option>
            <option value="image">Image</option>
        </select>
        @error('type') <p id="type-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
