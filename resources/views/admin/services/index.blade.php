@extends('admin.layouts.app')

@php
$showEditModal = $errors->hasAny(['name', 'icon', 'description', 'category', 'sort_order', 'is_active']);
$editingId = old('_editing', 0);
$editingData = null;
if ($editingId) {
    foreach ($servicesData as $data) {
        if ((int) $data['id'] === (int) $editingId) {
            $editingData = $data;
            break;
        }
    }
}
@endphp

@section('title', 'Services')
@section('header', 'Services')
@section('description', 'Manage resort services and amenities')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">Services</span>
    </nav>
@endsection

@section('content')
<div x-data="serviceModal()" class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 dark:border-slate-700">
            <form method="GET" class="flex flex-wrap gap-3 flex-1" x-data="liveSearchState()" @submit.prevent="goSearch()">
                <div class="relative flex-1 min-w-[180px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search services..." x-model="search" @input.debounce.350ms="goSearch()" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <select name="category" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All Categories</option>
                    <option value="Amenities" {{ request('category') === 'Amenities' ? 'selected' : '' }}>Amenities</option>
                    <option value="Dining" {{ request('category') === 'Dining' ? 'selected' : '' }}>Dining</option>
                    <option value="Activities" {{ request('category') === 'Activities' ? 'selected' : '' }}>Activities</option>
                    <option value="Events" {{ request('category') === 'Events' ? 'selected' : '' }}>Events</option>
                </select>
                <select name="is_active" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 bg-white dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                    <option value="">All</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-700 transition-colors">Filter</button>
                @if(request()->anyFilled(['search', 'category', 'is_active']))
                    <a href="{{ route('admin.services.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Clear</a>
                @endif
            </form>
            <button type="button" @@click="openCreate()" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-lg hover:bg-teal-700 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Service
            </button>
        </div>

        <div id="admin-live-loading" class="hidden" role="status">
            <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-slate-700">
                <svg class="w-4 h-4 text-teal-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <span class="text-sm text-gray-600 dark:text-slate-300">Loading results...</span>
            </div>
        </div>
        <div id="admin-table-region">
            @include('admin.services._table')
        </div>
    </div>

    {{-- Edit/Form Modal --}}
    <x-admin.modal name="service-form" size="lg">
        <form method="POST" :action="formAction">
            @csrf
            <input type="hidden" name="_method" :value="formMethod">
            <input type="hidden" name="_editing" :value="editingId || ''">

            @include('admin.services._form')

            <div class="flex items-center justify-end gap-3 pt-5 mt-6 border-t border-gray-100 dark:border-slate-700">
                <button type="button" @@click="window.dispatchEvent(new CustomEvent('close-modal-service-form'))" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">
                    <span x-text="isEditing ? 'Update Service' : 'Create Service'"></span>
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>

@include('admin.components.confirm-dialog', ['name' => 'delete', 'title' => 'Delete Service?', 'message' => 'Are you sure? This cannot be undone.'])
@endsection

@push('scripts')
<script>
function serviceModal() {
    return {
        services: @js($servicesData),
        isEditing: false,
        editingId: null,
        form: {
            name: '',
            icon: '',
            description: '',
            category: '',
            sort_order: 0,
            is_active: true,
        },
        formAction: '',
        formMethod: 'PUT',

        openCreate() {
            this.isEditing = false;
            this.editingId = null;
            this.form = {
                name: '',
                icon: '',
                description: '',
                category: '',
                sort_order: 0,
                is_active: true,
            };
            this.formAction = '{{ route('admin.services.store') }}';
            this.formMethod = 'POST';
            window.dispatchEvent(new CustomEvent('open-modal-service-form', { detail: { title: 'Add Service' } }));
        },

        openEdit(id) {
            const service = this.services.find(s => s.id === id);
            if (!service) return;
            this.isEditing = true;
            this.editingId = service.id;
            this.form = {
                name: service.name || '',
                icon: service.icon || '',
                description: service.description || '',
                category: service.category || '',
                sort_order: service.sort_order ?? 0,
                is_active: service.is_active !== false,
            };
            this.formAction = '/admin/services/' + service.id;
            this.formMethod = 'PUT';
            window.dispatchEvent(new CustomEvent('open-modal-service-form', { detail: { title: 'Edit Service: ' + service.name } }));
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

                const oldName = @js(old('name', ''));
                const oldIcon = @js(old('icon', ''));
                const oldDescription = @js(old('description', ''));
                const oldCategory = @js(old('category', ''));
                const oldSort = @js(old('sort_order', ''));
                const oldActive = @js(old('is_active'));

                this.$nextTick(() => {
                    if (oldName) this.form.name = oldName;
                    if (oldIcon) this.form.icon = oldIcon;
                    if (oldDescription) this.form.description = oldDescription;
                    if (oldCategory) this.form.category = oldCategory;
                    if (oldSort !== null && oldSort !== '') this.form.sort_order = oldSort;
                    if (oldActive !== null) this.form.is_active = true;
                });
            }
        },
    };
}
</script>
@endpush
