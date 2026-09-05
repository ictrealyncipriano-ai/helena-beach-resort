@extends('admin.layouts.app')

@php
$showModal = $errors->hasAny(['title', 'photo_path', 'category', 'sort_order', 'is_active']);
$editingId = old('_editing', 0);
$editingData = null;
if ($editingId) {
    foreach ($galleriesData as $data) {
        if ((int) $data['id'] === (int) $editingId) {
            $editingData = $data;
            break;
        }
    }
}
@endphp

@section('title', 'Gallery')
@section('header', 'Gallery')
@section('description', 'Manage resort gallery images')

@section('breadcrumb')
    <x-admin.breadcrumb :items="[['label' => 'Gallery']]" />
@endsection

@section('content')
<div x-data="galleryModal()" class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 dark:border-slate-700">
            <form method="GET" class="flex flex-wrap gap-3 flex-1">
                <div class="relative flex-1 min-w-[180px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search gallery..." class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <select name="category" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All Categories</option>
                    <option value="Resort" {{ request('category') === 'Resort' ? 'selected' : '' }}>Resort</option>
                    <option value="Beach" {{ request('category') === 'Beach' ? 'selected' : '' }}>Beach</option>
                    <option value="Food" {{ request('category') === 'Food' ? 'selected' : '' }}>Food</option>
                    <option value="Events" {{ request('category') === 'Events' ? 'selected' : '' }}>Events</option>
                </select>
                <select name="is_active" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors">Filter</button>
                @if(request()->anyFilled(['search', 'category', 'is_active']))
                    <a href="{{ route('admin.gallery.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Clear</a>
                @endif
            </form>
            <button type="button" @@click="openCreate()" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-lg hover:bg-teal-800 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Image
            </button>
        </div>

        @if($galleries->isEmpty())
            @include('components.admin.empty-state', ['icon' => 'photo', 'title' => 'No gallery images', 'message' => 'Add beautiful photos of your resort.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                            <th class="text-left px-5 py-3 font-medium">Image</th>
                            <th class="text-left px-5 py-3 font-medium">Title</th>
                            <th class="text-left px-5 py-3 font-medium">Category</th>
                            <th class="text-center px-5 py-3 font-medium">Active</th>
                            <th class="text-center px-5 py-3 font-medium hidden md:table-cell">Sort</th>
                            <th class="text-right px-5 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @foreach($galleries as $gallery)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                            <td class="px-5 py-3">
                                @if($gallery->photo_path)
                                    <img src="{{ Storage::url($gallery->photo_path) }}" alt="" class="w-12 h-12 rounded-lg object-cover border border-gray-200 dark:border-slate-600">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-slate-700/40 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-300 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/></svg>
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $gallery->title ?? 'Untitled' }}</td>
                            <td class="px-5 py-3">@include('components.admin.badge', ['type' => $gallery->category === 'Beach' ? 'info' : ($gallery->category === 'Food' ? 'warning' : ($gallery->category === 'Events' ? 'success' : 'gray')), 'slot' => $gallery->category ?? '—'])</td>
                            <td class="px-5 py-3 text-center">
                                @if($gallery->is_active)
                                    <svg class="w-5 h-5 text-emerald-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-gray-300 mx-auto dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center text-gray-500 hidden md:table-cell dark:text-slate-400">{{ $gallery->sort_order }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @@click="openEdit({{ $gallery->id }})" class="p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    <button type="button" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-500/10" aria-label="Delete" @@click="$dispatch('open-confirm-delete', { url: '{{ route('admin.gallery.destroy', $gallery) }}', method: 'DELETE' })">
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
                @include('components.admin.pagination', ['paginator' => $galleries])
            </div>
        @endif
    </div>

    {{-- Form Modal --}}
    <x-admin.modal name="gallery-form" size="lg">
        <form method="POST" :action="formAction" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" :value="formMethod">
            <input type="hidden" name="_editing" :value="editingId || ''">

            @include('admin.gallery._form')

            <div class="flex items-center justify-end gap-3 pt-5 mt-6 border-t border-gray-100 dark:border-slate-700">
                <button type="button" @@click="window.dispatchEvent(new CustomEvent('close-modal-gallery-form'))" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">
                    <span x-text="isEditing ? 'Update Image' : 'Upload Image'"></span>
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>

<x-admin.confirm-dialog name="delete" title="Delete Image?" message="Are you sure? This cannot be undone." />
@endsection

<script>
window.galleryModal = function() {
    return {
        galleries: @js($galleriesData),
        isEditing: false,
        editingId: null,
        form: {
            title: '',
            category: '',
            sort_order: 0,
            is_active: true,
            photo_url: null,
        },
        formAction: '',
        formMethod: 'POST',

        openCreate() {
            this.isEditing = false;
            this.editingId = null;
            this.form = {
                title: '',
                category: '',
                sort_order: 0,
                is_active: true,
                photo_url: null,
            };
            this.formAction = '{{ route('admin.gallery.store') }}';
            this.formMethod = 'POST';
            window.dispatchEvent(new CustomEvent('open-modal-gallery-form', { detail: { title: 'Add Image' } }));
        },

        openEdit(id) {
            const gallery = this.galleries.find(g => g.id === id);
            if (!gallery) return;
            this.isEditing = true;
            this.editingId = gallery.id;
            this.form = {
                title: gallery.title || '',
                category: gallery.category || '',
                sort_order: gallery.sort_order ?? 0,
                is_active: gallery.is_active,
                photo_url: gallery.photo_url || null,
            };
            this.formAction = '/admin/gallery/' + gallery.id;
            this.formMethod = 'PUT';
            window.dispatchEvent(new CustomEvent('open-modal-gallery-form', { detail: { title: 'Edit Image' } }));
        },

        init() {
            const showModal = @js($showModal);
            const editingId = @js($editingData ? $editingData['id'] : 0);
            const oldTitle = @js(old('title', ''));
            const oldCategory = @js(old('category', ''));
            const oldSortOrder = @js(old('sort_order', ''));

            if (showModal) {
                if (editingId) {
                    this.openEdit(Number(editingId));
                    this.$nextTick(() => {
                        if (oldTitle) this.form.title = oldTitle;
                        if (oldCategory) this.form.category = oldCategory;
                        if (oldSortOrder !== '' && oldSortOrder !== null) this.form.sort_order = oldSortOrder;
                    });
                } else {
                    this.openCreate();
                    this.$nextTick(() => {
                        if (oldTitle) this.form.title = oldTitle;
                        if (oldCategory) this.form.category = oldCategory;
                        if (oldSortOrder !== '' && oldSortOrder !== null) this.form.sort_order = oldSortOrder;
                    });
                }
            }
        },
    };
}
</script>
