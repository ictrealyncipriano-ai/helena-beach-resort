<div class="space-y-6">
    {{-- Account Information --}}
    <div class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" x-model="form.name" required maxlength="255" placeholder="e.g. Juan Dela Cruz"
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all @error('name') border-red-300 @enderror">
            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address <span class="text-red-500">*</span></label>
            <input type="email" name="email" x-model="form.email" required maxlength="255" placeholder="e.g. juan@helena.com"
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all @error('email') border-red-300 @enderror">
            @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Password <template x-if="!isEditing"><span class="text-red-500">*</span></template>
                <template x-if="isEditing"><span class="text-gray-400 font-normal">(leave blank to keep current)</span></template>
            </label>
            <input type="password" name="password" x-model="form.password" minlength="8" :required="!isEditing"
                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all @error('password') border-red-300 @enderror">
            @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <template x-if="!isEditing">
                <div>
                    <div class="mt-2 h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300" x-bind:style="'width: ' + Math.min(100, (form.password.length / 12) * 100) + '%; background: ' + (form.password.length >= 12 ? '#10b981' : form.password.length >= 8 ? '#f59e0b' : '#d1d5db')"></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Min 8 characters. Longer passwords are stronger.</p>
                </div>
            </template>
        </div>
    </div>

    {{-- Role Selection --}}
    <div>
        <h4 class="text-sm font-semibold text-gray-900 mb-3">Role & Permissions</h4>
        @error('role') <p class="mb-3 text-xs text-red-500">{{ $message }}</p> @enderror
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <label class="relative cursor-pointer">
                <input type="radio" name="role" value="super_admin" x-model="form.role" class="sr-only peer">
                <div class="rounded-xl border-2 p-4 transition-all duration-200 peer-checked:border-red-500 peer-checked:bg-red-50/50 border-gray-200 hover:border-gray-300 bg-white">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">Super Admin</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">Full access to all modules including site settings and user management.</p>
                </div>
            </label>
            <label class="relative cursor-pointer">
                <input type="radio" name="role" value="admin" x-model="form.role" class="sr-only peer">
                <div class="rounded-xl border-2 p-4 transition-all duration-200 peer-checked:border-teal-500 peer-checked:bg-teal-50/50 border-gray-200 hover:border-gray-300 bg-white">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-9 h-9 rounded-full bg-teal-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">Admin</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">Can manage content, bookings, inquiries, and guests.</p>
                </div>
            </label>
            <label class="relative cursor-pointer">
                <input type="radio" name="role" value="staff" x-model="form.role" class="sr-only peer">
                <div class="rounded-xl border-2 p-4 transition-all duration-200 peer-checked:border-gray-400 peer-checked:bg-gray-50/50 border-gray-200 hover:border-gray-300 bg-white">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">Staff</span>
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">Dashboard and inquiries only. Read-only access.</p>
                </div>
            </label>
        </div>
    </div>
</div>
