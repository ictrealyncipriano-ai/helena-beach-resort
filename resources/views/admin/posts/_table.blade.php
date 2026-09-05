@if($posts->isEmpty())
    @include('components.admin.empty-state', [
        'title' => 'No posts',
        'message' => 'Write an announcement or news post to share with guests.',
    ])
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                    <th class="text-left px-5 py-3 font-medium">Cover</th>
                    <th class="text-left px-5 py-3 font-medium">Title</th>
                    <th class="text-left px-5 py-3 font-medium">Status</th>
                    <th class="text-left px-5 py-3 font-medium hidden md:table-cell">Published</th>
                    <th class="text-right px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                @foreach($posts as $post)
                <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                    <td class="px-5 py-3">
                        @if($post->cover_image)
                            <img src="{{ Storage::url($post->cover_image) }}" alt="" class="w-14 h-10 rounded-lg object-cover border border-gray-200 dark:border-slate-600">
                        @else
                            <div class="w-14 h-10 rounded-lg bg-gray-100 dark:bg-slate-700/40 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-300 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        @endif
                    </td>
                    <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">
                        {{ $post->title }}
                        <div class="text-xs font-normal text-gray-500 dark:text-slate-400 mt-0.5">{{ Str::limit($post->excerpt, 50) }}</div>
                    </td>
                    <td class="px-5 py-3">
                        @php
                            $status = ! $post->is_active ? 'inactive' : ($post->published_at === null ? 'draft' : ($post->published_at->isFuture() ? 'scheduled' : 'published'));
                            $badgeType = match ($status) {
                                'published' => 'success',
                                'scheduled' => 'warning',
                                'draft' => 'gray',
                                default => 'danger',
                            };
                        @endphp
                        @include('components.admin.badge', ['type' => $badgeType, 'slot' => ucfirst($status)])
                    </td>
                    <td class="px-5 py-3 text-gray-600 hidden md:table-cell dark:text-slate-300">
                        {{ $post->published_at ? $post->published_at->format('M j, Y g:i A') : '—' }}
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('news.show', $post) }}" target="_blank" rel="noopener" class="p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>
                            <a href="{{ route('admin.posts.edit', $post) }}" class="p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            </a>
                            <button type="button" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-500/10" aria-label="Delete" @@click="$dispatch('open-confirm-delete', { url: '{{ route('admin.posts.destroy', $post) }}', method: 'DELETE' })">
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
        <x-admin.pagination :paginator="$posts" live />
    </div>
@endif

<span data-total="{{ $posts->total() }}" class="hidden" aria-hidden="true"></span>
