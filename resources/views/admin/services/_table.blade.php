@if($services->isEmpty())
    @include('components.admin.empty-state', [
        'title' => 'No services',
        'message' => 'Add services to display on your website.',
        'actionClick' => 'openCreate()',
        'actionLabel' => 'Add Service',
    ])
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                    <th class="text-left px-5 py-3 font-medium">Icon</th>
                    <th class="text-left px-5 py-3 font-medium">Name</th>
                    <th class="text-left px-5 py-3 font-medium">Category</th>
                    <th class="text-left px-5 py-3 font-medium hidden sm:table-cell">Description</th>
                    <th class="text-center px-5 py-3 font-medium">Active</th>
                    <th class="text-center px-5 py-3 font-medium hidden md:table-cell">Sort</th>
                    <th class="text-right px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                @foreach($services as $service)
                <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                    <td class="px-5 py-3 text-gray-500 font-mono text-xs dark:text-slate-400">{{ $service->icon }}</td>
                    <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $service->name }}</td>
                    <td class="px-5 py-3">@include('components.admin.badge', ['type' => $service->category === 'Amenities' ? 'info' : ($service->category === 'Dining' ? 'warning' : ($service->category === 'Activities' ? 'success' : ($service->category === 'Events' ? 'danger' : 'gray'))), 'slot' => $service->category ?? '—'])</td>
                    <td class="px-5 py-3 text-gray-600 max-w-xs truncate hidden sm:table-cell dark:text-slate-300">{{ Str::limit($service->description, 60) }}</td>
                    <td class="px-5 py-3 text-center">
                        @if($service->is_active)
                            <svg class="w-5 h-5 text-emerald-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <svg class="w-5 h-5 text-gray-300 mx-auto dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center text-gray-500 hidden md:table-cell dark:text-slate-400">{{ $service->sort_order }}</td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" @@click="openEdit({{ $service->id }})" class="p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            </button>
                            <button type="button" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-500/10" aria-label="Delete"
                                @@click="$dispatch('open-confirm-delete', { url: '{{ route('admin.services.destroy', $service) }}', method: 'DELETE' })">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-700">
        <x-admin.pagination :paginator="$services" live />
    </div>
@endif

<span data-total="{{ $services->total() }}" class="hidden" aria-hidden="true"></span>
