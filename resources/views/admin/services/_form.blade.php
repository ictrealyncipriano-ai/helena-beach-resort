<div class="space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="name-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Name <span class="text-red-600">*</span></label>
            <input type="text" name="name" id="name-field" x-model="form.name" required maxlength="255"
                @error('name') aria-invalid="true" aria-describedby="name-field-error" @enderror
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('name') border-red-300 dark:border-red-500 @enderror">
            @error('name') <p id="name-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="icon-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Icon</label>
            <input type="text" name="icon" id="icon-field" x-model="form.icon" maxlength="50" placeholder="e.g. wifi, pool, parking"
                @error('icon') aria-invalid="true" aria-describedby="icon-field-error" @enderror
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('icon') border-red-300 dark:border-red-500 @enderror">
            @error('icon') <p id="icon-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="description-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Description</label>
        <textarea name="description" id="description-field" rows="4" x-model="form.description"
            @error('description') aria-invalid="true" aria-describedby="description-field-error" @enderror
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('description') border-red-300 dark:border-red-500 @enderror"></textarea>
        @error('description') <p id="description-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="category-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Category</label>
            <select name="category" id="category-field" x-model="form.category"
                @error('category') aria-invalid="true" aria-describedby="category-field-error" @enderror
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('category') border-red-300 dark:border-red-500 @enderror">
                <option value="">None</option>
                <option value="Amenities">Amenities</option>
                <option value="Dining">Dining</option>
                <option value="Activities">Activities</option>
                <option value="Events">Events</option>
            </select>
            @error('category') <p id="category-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
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
