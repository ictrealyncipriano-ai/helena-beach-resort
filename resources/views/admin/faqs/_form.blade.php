<div class="space-y-5">
    <div>
        <label for="question-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Question <span class="text-red-600">*</span></label>
        <input type="text" name="question" id="question-field" x-model="form.question" required maxlength="255"
            @error('question') aria-invalid="true" aria-describedby="question-field-error" @enderror
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('question') border-red-300 dark:border-red-500 @enderror">
        @error('question') <p id="question-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="answer-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Answer <span class="text-red-600">*</span></label>
        <textarea name="answer" id="answer-field" rows="4" x-model="form.answer" required
            @error('answer') aria-invalid="true" aria-describedby="answer-field-error" @enderror
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('answer') border-red-300 dark:border-red-500 @enderror"></textarea>
        @error('answer') <p id="answer-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="sort-order-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Sort Order</label>
            <input type="number" name="sort_order" id="sort-order-field" x-model="form.sort_order" min="0"
                @error('sort_order') aria-invalid="true" aria-describedby="sort-order-field-error" @enderror
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('sort_order') border-red-300 dark:border-red-500 @enderror">
            @error('sort_order') <p id="sort-order-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-end pb-2">
            <label for="is-active-field" class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="is-active-field" name="is_active" value="1" x-model="form.is_active" class="sr-only peer">
                <div class="w-9 h-5 bg-gray-200 peer-checked:bg-teal-700 dark:bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-teal-600 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                <span class="ms-2 text-sm font-medium text-gray-700 dark:text-slate-300">Active</span>
            </label>
        </div>
    </div>
</div>
