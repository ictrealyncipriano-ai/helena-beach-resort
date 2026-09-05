@if($logs->isEmpty())
    @include('components.admin.empty-state', [
        'title' => 'No activity recorded',
        'message' => 'No audit entries match these filters.',
    ])
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                    <th class="text-left px-5 py-3 font-medium">When</th>
                    <th class="text-left px-5 py-3 font-medium">Action</th>
                    <th class="text-left px-5 py-3 font-medium">Description</th>
                    <th class="text-left px-5 py-3 font-medium hidden md:table-cell">Actor</th>
                    <th class="text-right px-5 py-3 font-medium hidden sm:table-cell">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                @foreach($logs as $log)
                <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                    <td class="px-5 py-3 text-xs text-gray-500 whitespace-nowrap dark:text-slate-400">
                        {{ $log->created_at->format('M d, Y g:i A') }}
                    </td>
                    <td class="px-5 py-3">
                        <span class="font-mono text-xs text-gray-600 dark:text-slate-300">{{ $log->action }}</span>
                    </td>
                    <td class="px-5 py-3 text-gray-700 max-w-md dark:text-slate-200">
                        {{ Str::limit($log->description ?? '—', 90) }}
                    </td>
                    <td class="px-5 py-3 hidden md:table-cell">
                        <span class="{{ $log->user_name ? 'text-gray-700 dark:text-slate-200' : 'text-gray-400 dark:text-slate-500' }}">{{ $log->actorLabel() }}</span>
                    </td>
                    <td class="px-5 py-3 text-right hidden sm:table-cell">
                        <a href="{{ route('admin.activity-logs.show', $log) }}" class="inline-flex items-center gap-1 p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="View details">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-700">
        <x-admin.pagination :paginator="$logs" live />
    </div>
@endif

<span data-total="{{ $logs->total() }}" class="hidden" aria-hidden="true"></span>
