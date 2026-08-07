@extends('admin.layouts.app')

@php
$showEditModal = $errors->hasAny(['guest_name', 'content', 'rating', 'cottage_id', 'is_active', 'sort_order']);
$editingId = old('_editing', 0);
$editingData = null;
if ($editingId) {
    foreach ($testimonialsData as $data) {
        if ((int) $data['id'] === (int) $editingId) {
            $editingData = $data;
            break;
        }
    }
}
@endphp

@section('title', 'Testimonials')
@section('header', 'Testimonials')
@section('description', 'Manage guest reviews and testimonials')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">Testimonials</span>
    </nav>
@endsection

@section('content')
<div x-data="testimonialModal()" class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 dark:border-slate-700">
            <form method="GET" class="flex flex-wrap gap-3 flex-1">
                <div class="relative flex-1 min-w-[180px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search testimonials..." class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <select name="rating" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All Ratings</option>
                    @foreach(range(1,5) as $r)
                        <option value="{{ $r }}" {{ request('rating') == $r ? 'selected' : '' }}>{{ $r }} Star{{ $r > 1 ? 's' : '' }}</option>
                    @endforeach
                </select>
                <select name="is_active" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All Status</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-700 transition-colors">Filter</button>
                @if(request()->anyFilled(['search', 'rating', 'is_active']))
                    <a href="{{ route('admin.testimonials.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Clear</a>
                @endif
            </form>
            <button type="button" @@click="openCreate()" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-lg hover:bg-teal-700 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Testimonial
            </button>
        </div>

        @if($testimonials->isEmpty())
            @include('admin.components.empty-state', [
                'title' => 'No testimonials',
                'message' => 'Guest reviews will appear here.',
                'actionClick' => 'openCreate()',
                'actionLabel' => 'Add Testimonial',
            ])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                            <th class="text-left px-5 py-3 font-medium">Guest Name</th>
                            <th class="text-left px-5 py-3 font-medium">Rating</th>
                            <th class="text-left px-5 py-3 font-medium">Content</th>
                            <th class="text-left px-5 py-3 font-medium hidden sm:table-cell">Cottage</th>
                            <th class="text-center px-5 py-3 font-medium">Active</th>
                            <th class="text-center px-5 py-3 font-medium hidden md:table-cell">Sort</th>
                            <th class="text-right px-5 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @foreach($testimonials as $testimonial)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                            <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $testimonial->guest_name }}</td>
                            <td class="px-5 py-3">
                                <span class="text-amber-500 dark:text-amber-400">{{ str_repeat('★', $testimonial->rating) }}{{ str_repeat('☆', 5 - $testimonial->rating) }}</span>
                            </td>
                            <td class="px-5 py-3 text-gray-600 max-w-xs truncate dark:text-slate-300">{{ Str::limit($testimonial->content, 80) }}</td>
                            <td class="px-5 py-3 hidden sm:table-cell">@include('admin.components.badge', ['type' => 'primary', 'slot' => $testimonial->cottage?->name ?? 'N/A'])</td>
                            <td class="px-5 py-3 text-center">
                                @if($testimonial->is_active)
                                    <svg class="w-5 h-5 text-emerald-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-300 mx-auto dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center text-gray-500 hidden md:table-cell dark:text-slate-400">{{ $testimonial->sort_order }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @@click="openEdit({{ $testimonial->id }})" class="p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    <button type="button" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-500/10" aria-label="Delete"
                                        @@click="$dispatch('open-confirm-delete', { url: '{{ route('admin.testimonials.destroy', $testimonial) }}', method: 'DELETE' })">
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
                @include('admin.components.pagination', ['paginator' => $testimonials])
            </div>
        @endif
    </div>

    {{-- Edit/Form Modal --}}
    <x-admin.modal name="testimonial-form" size="lg">
        <form method="POST" :action="formAction" enctype="multipart/form-data" @@submit="attachAvatar($event)">
            @csrf
            <input type="hidden" name="_method" :value="formMethod">
            <input type="hidden" name="_editing" :value="editingId || ''">

            @include('admin.testimonials._form')

            <div class="flex items-center justify-end gap-3 pt-5 mt-6 border-t border-gray-100 dark:border-slate-700">
                <button type="button" @@click="window.dispatchEvent(new CustomEvent('close-modal-testimonial-form'))" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">
                    <span x-text="isEditing ? 'Update Testimonial' : 'Create Testimonial'"></span>
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>

@include('admin.components.confirm-dialog', ['name' => 'delete', 'title' => 'Delete Testimonial?', 'message' => 'Are you sure? This cannot be undone.'])
@endsection

@push('scripts')
<script>
function testimonialModal() {
    return {
        testimonials: @js($testimonialsData),
        isEditing: false,
        editingId: null,
        avatarFile: null,
        form: {
            guest_name: '',
            rating: 5,
            content: '',
            cottage_id: '',
            sort_order: 0,
            is_active: true,
            avatar_url: '',
        },
        formAction: '',
        formMethod: 'PUT',

        get avatarUrl() {
            return this.avatarFile ? this.avatarFile.url : (this.form.avatar_url || '');
        },

        handleAvatarUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.avatarFile = { file, url: URL.createObjectURL(file) };
            event.target.value = '';
        },

        attachAvatar(event) {
            if (!this.avatarFile) return;
            const dt = new DataTransfer();
            dt.items.add(this.avatarFile.file);
            const input = event.target.querySelector('input[name="guest_avatar"]');
            if (input) input.files = dt.files;
        },

        openCreate() {
            this.isEditing = false;
            this.editingId = null;
            this.avatarFile = null;
            this.form = {
                guest_name: '',
                rating: 5,
                content: '',
                cottage_id: '',
                sort_order: 0,
                is_active: true,
                avatar_url: '',
            };
            this.formAction = '{{ route('admin.testimonials.store') }}';
            this.formMethod = 'POST';
            window.dispatchEvent(new CustomEvent('open-modal-testimonial-form', { detail: { title: 'Add Testimonial' } }));
        },

        openEdit(id) {
            const testimonial = this.testimonials.find(t => t.id === id);
            if (!testimonial) return;
            this.isEditing = true;
            this.editingId = testimonial.id;
            this.avatarFile = null;
            this.form = {
                guest_name: testimonial.guest_name || '',
                rating: testimonial.rating || 5,
                content: testimonial.content || '',
                cottage_id: testimonial.cottage_id || '',
                sort_order: testimonial.sort_order ?? 0,
                is_active: testimonial.is_active !== false,
                avatar_url: testimonial.guest_avatar || '',
            };
            this.formAction = '/admin/testimonials/' + testimonial.id;
            this.formMethod = 'PUT';
            window.dispatchEvent(new CustomEvent('open-modal-testimonial-form', { detail: { title: 'Edit Testimonial: ' + testimonial.guest_name } }));
        },

        init() {
            const showModal = @js($showEditModal);
            const editingId = @js($editingData ? $editingData['id'] : 0);

            if (showModal) {
                if (editingId) {
                    this.openEdit(Number(editingId));
                } else {
                    this.openCreate();
                }

                const oldName = @js(old('guest_name', ''));
                const oldRating = @js(old('rating', ''));
                const oldContent = @js(old('content', ''));
                const oldCottageId = @js(old('cottage_id', ''));
                const oldSort = @js(old('sort_order', ''));
                const oldActive = @js(old('is_active'));

                this.$nextTick(() => {
                    if (oldName) this.form.guest_name = oldName;
                    if (oldRating) this.form.rating = oldRating;
                    if (oldContent) this.form.content = oldContent;
                    if (oldCottageId) this.form.cottage_id = oldCottageId;
                    if (oldSort !== null && oldSort !== '') this.form.sort_order = oldSort;
                    if (oldActive !== null) this.form.is_active = true;
                });
            }
        },
    };
}
</script>
@endpush
