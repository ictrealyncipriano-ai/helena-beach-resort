<div class="space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="guest-name-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Guest Name <span class="text-red-600">*</span></label>
            <input type="text" name="guest_name" id="guest-name-field" x-model="form.guest_name" required maxlength="255"
                @error('guest_name') aria-invalid="true" aria-describedby="guest-name-field-error" @enderror
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('guest_name') border-red-300 dark:border-red-500 @enderror">
            @error('guest_name') <p id="guest-name-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="rating-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Rating <span class="text-red-600">*</span></label>
            <select name="rating" id="rating-field" x-model="form.rating" required
                @error('rating') aria-invalid="true" aria-describedby="rating-field-error" @enderror
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('rating') border-red-300 dark:border-red-500 @enderror">
                @foreach(range(1,5) as $r)
                    <option value="{{ $r }}">{{ $r }} Star{{ $r > 1 ? 's' : '' }}</option>
                @endforeach
            </select>
            @error('rating') <p id="rating-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="content-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Content <span class="text-red-600">*</span></label>
        <textarea name="content" id="content-field" rows="4" x-model="form.content" required
            @error('content') aria-invalid="true" aria-describedby="content-field-error" @enderror
            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('content') border-red-300 dark:border-red-500 @enderror"></textarea>
        @error('content') <p id="content-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <span class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Guest Avatar</span>
        <div class="flex items-center gap-3">
            <template x-if="avatarUrl">
                <img :src="avatarUrl" class="w-14 h-14 rounded-full object-cover border border-gray-200 dark:border-slate-600">
            </template>
            <label class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-teal-700 bg-teal-50 rounded-lg hover:bg-teal-100 cursor-pointer transition-colors dark:bg-teal-900/30 dark:text-teal-300 dark:hover:bg-teal-900/40">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg>
                <span x-text="avatarUrl ? 'Change Photo' : 'Upload Photo'"></span>
                <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @@change="handleAvatarUpload($event)">
            </label>
        </div>
        <input type="file" name="guest_avatar" accept="image/jpeg,image/png,image/webp" class="hidden">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="cottage-id-field" class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Cottage</label>
            <select name="cottage_id" id="cottage-id-field" x-model="form.cottage_id"
                @error('cottage_id') aria-invalid="true" aria-describedby="cottage-id-field-error" @enderror
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/40 @error('cottage_id') border-red-300 dark:border-red-500 @enderror">
                <option value="">None</option>
                @foreach($cottages as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            @error('cottage_id') <p id="cottage-id-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
