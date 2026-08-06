<div class="space-y-5">
    <div>
        <h4 class="text-sm font-semibold text-gray-900 mb-3 dark:text-white">Image Details</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Title</label>
                <input type="text" name="title" x-model="form.title" maxlength="255"
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('title') border-red-300 dark:border-red-500 @enderror">
                @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Image <span class="text-red-500" x-show="!isEditing">*</span></label>
                <template x-if="isEditing && form.photo_url">
                    <div class="mb-2">
                        <img x-bind:src="form.photo_url" alt="" class="w-32 h-24 rounded-lg object-cover border border-gray-200 dark:border-slate-600">
                    </div>
                </template>
                <input type="file" name="photo_path" accept="image/jpeg,image/png,image/webp" :required="!isEditing"
                    class="block w-full text-sm text-gray-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 dark:file:bg-teal-900/30 dark:file:text-teal-300 dark:hover:file:bg-teal-900/40 @error('photo_path') border border-red-300 rounded-lg @enderror">
                @error('photo_path') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500" x-show="isEditing">Leave blank to keep the current image.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Category</label>
                <select name="category" x-model="form.category"
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('category') border-red-300 dark:border-red-500 @enderror">
                    <option value="">None</option>
                    <option value="Resort">Resort</option>
                    <option value="Beach">Beach</option>
                    <option value="Food">Food</option>
                    <option value="Events">Events</option>
                </select>
                @error('category') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Sort Order</label>
                <input type="number" name="sort_order" x-model="form.sort_order" min="0"
                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20 @error('sort_order') border-red-300 dark:border-red-500 @enderror">
                @error('sort_order') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-gray-900 mb-3 dark:text-white">Visibility</h4>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="sr-only peer">
            <div class="w-9 h-5 bg-gray-200 peer-checked:bg-teal-600 dark:bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
            <span class="ms-2 text-sm font-medium text-gray-700 dark:text-slate-300">Active</span>
        </label>
        @error('is_active') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
</div>
