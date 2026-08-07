<div class="space-y-6">
    {{-- Account Information --}}
    <div class="space-y-5">
        <div>
            <label for="name-field" class="block text-sm font-medium text-gray-700 mb-1.5 dark:text-slate-200">Full Name <span class="text-red-600">*</span></label>
            <input type="text" name="name" id="name-field" x-model="form.name" required maxlength="255" placeholder="e.g. Juan Dela Cruz"
                @error('name') aria-invalid="true" aria-describedby="name-field-error" @enderror
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('name') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
            @error('name') <p id="name-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="email-field" class="block text-sm font-medium text-gray-700 mb-1.5 dark:text-slate-200">Email Address <span class="text-red-600">*</span></label>
            <input type="email" name="email" id="email-field" x-model="form.email" required maxlength="255" placeholder="e.g. juan@helena.com"
                @error('email') aria-invalid="true" aria-describedby="email-field-error" @enderror
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('email') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
            @error('email') <p id="email-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password-field" class="block text-sm font-medium text-gray-700 mb-1.5 dark:text-slate-200">
                Password <template x-if="!isEditing"><span class="text-red-600">*</span></template>
                <template x-if="isEditing"><span class="text-gray-500 font-normal dark:text-slate-400">(leave blank to keep current)</span></template>
            </label>
            <input type="password" name="password" id="password-field" x-model="form.password" minlength="8" :required="!isEditing"
                @error('password') aria-invalid="true" aria-describedby="password-field-error" @enderror
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-500 transition-all @error('password') border-red-300 @enderror dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/40">
            @error('password') <p id="password-field-error" class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <template x-if="!isEditing">
                <div>
                    <div class="mt-2 h-1.5 w-full bg-gray-100 rounded-full overflow-hidden dark:bg-slate-700">
                        <div class="h-full rounded-full transition-all duration-300" x-bind:style="'width: ' + Math.min(100, (form.password.length / 12) * 100) + '%; background: ' + (form.password.length >= 12 ? '#10b981' : form.password.length >= 8 ? '#f59e0b' : '#d1d5db')"></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Min 8 characters. Longer passwords are stronger.</p>
                </div>
            </template>
        </div>
    </div>

    {{-- Role Selection --}}
    <div>
        <fieldset>
            <legend class="text-sm font-semibold text-gray-900 mb-3 dark:text-white">Role & Permissions</legend>
            @error('role') <p id="role-field-error" class="mb-3 text-xs text-red-600" role="alert">{{ $message }}</p> @enderror
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <label class="relative cursor-pointer">
                    <input type="radio" name="role" value="super_admin" x-model="form.role" class="sr-only peer" @error('role') aria-invalid="true" aria-describedby="role-field-error" @enderror>
                    <div class="rounded-xl border-2 p-4 transition-all duration-200 peer-checked:border-red-500 peer-checked:bg-red-50/50 border-gray-200 hover:border-gray-300 bg-white dark:border-slate-600 dark:hover:border-slate-500 dark:bg-slate-800 dark:peer-checked:bg-red-500/10">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center dark:bg-red-500/10">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Super Admin</span>
                        </div>
                        <p class="text-xs text-gray-500 leading-relaxed dark:text-slate-400">Full access to all modules including site settings and user management.</p>
                    </div>
                </label>
                <label class="relative cursor-pointer">
                    <input type="radio" name="role" value="admin" x-model="form.role" class="sr-only peer" @error('role') aria-invalid="true" aria-describedby="role-field-error" @enderror>
                    <div class="rounded-xl border-2 p-4 transition-all duration-200 peer-checked:border-teal-500 peer-checked:bg-teal-50/50 border-gray-200 hover:border-gray-300 bg-white dark:border-slate-600 dark:hover:border-slate-500 dark:bg-slate-800 dark:peer-checked:bg-teal-500/10">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 rounded-full bg-teal-100 flex items-center justify-center dark:bg-teal-500/10">
                                <svg class="w-4 h-4 text-teal-700 dark:text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Admin</span>
                        </div>
                        <p class="text-xs text-gray-500 leading-relaxed dark:text-slate-400">Can manage content, bookings, inquiries, and guests.</p>
                    </div>
                </label>
                <label class="relative cursor-pointer">
                    <input type="radio" name="role" value="staff" x-model="form.role" class="sr-only peer" @error('role') aria-invalid="true" aria-describedby="role-field-error" @enderror>
                    <div class="rounded-xl border-2 p-4 transition-all duration-200 peer-checked:border-gray-400 peer-checked:bg-gray-50/50 border-gray-200 hover:border-gray-300 bg-white dark:border-slate-600 dark:hover:border-slate-500 dark:bg-slate-800 dark:peer-checked:bg-slate-500/10">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center dark:bg-slate-700">
                                <svg class="w-4 h-4 text-gray-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Staff</span>
                        </div>
                        <p class="text-xs text-gray-500 leading-relaxed dark:text-slate-400">Dashboard and inquiries only. Read-only access.</p>
                    </div>
                </label>
            </div>
        </fieldset>
    </div>
</div>
