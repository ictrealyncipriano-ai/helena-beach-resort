    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-0.5 gradient-accent-teal"></div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl stat-icon-teal flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ $users->total() }}</p>
                    <p class="text-sm text-gray-500 font-medium">Total Users</p>
                </div>
            </div>
        </div>
        <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-0.5 gradient-accent-amber"></div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl stat-icon-amber flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ collect($users->items())->where('role', 'admin')->count() + collect($users->items())->where('role', 'super_admin')->count() }}</p>
                    <p class="text-sm text-gray-500 font-medium">Admins & Super Admins</p>
                </div>
            </div>
        </div>
        <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-0.5 gradient-accent-gray"></div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl stat-icon-gray flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 tracking-tight">{{ collect($users->items())->where('role', 'staff')->count() }}</p>
                    <p class="text-sm text-gray-500 font-medium">Staff</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <form method="GET" class="flex gap-3 flex-1 max-w-md">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" id="user-search" name="search" value="{{ request('search') }}" placeholder="Search users..." class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-teal-400 transition-all">
                </div>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors">Search</button>
                @if(request('search'))
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Clear</a>
                @endif
            </form>
            <button type="button" @@click="openCreate()" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add User
            </button>
        </div>

        @if($users->isEmpty())
            @include('admin.components.empty-state', [
                'icon' => 'inbox',
                'title' => 'No users yet',
                'message' => 'Create your first admin user to get started.',
            ])
        @else
            {{-- Desktop Table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                            <th class="text-left px-5 py-3 font-medium">User</th>
                            <th class="text-left px-5 py-3 font-medium">Email</th>
                            <th class="text-left px-5 py-3 font-medium">Role</th>
                            <th class="text-left px-5 py-3 font-medium">Joined</th>
                            <th class="text-right px-5 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($users as $user)
                        @php $userJson = $user->only('id', 'name', 'email', 'role'); @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0 {{ $user->role === 'super_admin' ? 'bg-red-500' : ($user->role === 'admin' ? 'bg-teal-500' : 'bg-gray-400') }}">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr($user->name, strpos($user->name, ' ') !== false ? strpos($user->name, ' ') + 1 : 1, 1)) }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $user->email }}</td>
                            <td class="px-5 py-3">
                                @if($user->role === 'super_admin')
                                    @include('admin.components.badge', ['type' => 'danger', 'slot' => 'Super Admin'])
                                @elseif($user->role === 'admin')
                                    @include('admin.components.badge', ['type' => 'warning', 'slot' => 'Admin'])
                                @else
                                    @include('admin.components.badge', ['type' => 'gray', 'slot' => 'Staff'])
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @@click="openEdit(JSON.parse($el.getAttribute('data-user')))" data-user='@json($userJson)' class="p-1.5 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    @if($user->id !== auth()->id())
                                    <button type="button" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete"
                                        @@click="$dispatch('open-confirm-delete', { url: '{{ route('admin.users.destroy', $user) }}', method: 'DELETE' })">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="sm:hidden divide-y divide-gray-50">
                @foreach($users as $user)
                @php $userJson = $user->only('id', 'name', 'email', 'role'); @endphp
                <div class="p-4 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0 {{ $user->role === 'super_admin' ? 'bg-red-500' : ($user->role === 'admin' ? 'bg-teal-500' : 'bg-gray-400') }}">
                            {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr($user->name, strpos($user->name, ' ') !== false ? strpos($user->name, ' ') + 1 : 1, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @@click="openEdit(JSON.parse($el.getAttribute('data-user')))" data-user='@json($userJson)' class="p-2 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            </button>
                            @if($user->id !== auth()->id())
                            <button type="button" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                @@click="$dispatch('open-confirm-delete', { url: '{{ route('admin.users.destroy', $user) }}', method: 'DELETE' })">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            @if($user->role === 'super_admin')
                                @include('admin.components.badge', ['type' => 'danger', 'slot' => 'Super Admin'])
                            @elseif($user->role === 'admin')
                                @include('admin.components.badge', ['type' => 'warning', 'slot' => 'Admin'])
                            @else
                                @include('admin.components.badge', ['type' => 'gray', 'slot' => 'Staff'])
                            @endif
                        </div>
                        <span class="text-xs text-gray-400">{{ $user->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="px-5 py-4 border-t border-gray-100">
                @include('admin.components.pagination', ['paginator' => $users])
            </div>
        @endif