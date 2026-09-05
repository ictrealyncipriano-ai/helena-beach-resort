@extends('admin.layouts.app')

@php
$showEditModal = $errors->hasAny(['key', 'value', 'type']);
$editingId = old('_editing', 0);
$editingData = null;
if ($editingId) {
    foreach ($settingsData as $data) {
        if ((int) $data['id'] === (int) $editingId) {
            $editingData = $data;
            break;
        }
    }
}
@endphp

@section('title', 'Site Settings')
@section('header', 'Site Settings')
@section('description', 'Manage application settings')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">Site Settings</span>
    </nav>
@endsection

@section('content')
<div x-data="siteSettingModal()" class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 dark:border-slate-700">
            <form method="GET" class="flex flex-wrap items-center gap-3 flex-1" x-data="liveSearchState()" @submit.prevent="goSearch()">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search settings by key or value..." x-model="search" @input.debounce.350ms="goSearch()" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors">Filter</button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.site-settings.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Clear</a>
                @endif
            </form>
            <p class="text-sm text-gray-500 dark:text-slate-400"><span data-live-count>{{ $settings->total() }}</span> settings</p>
            <button type="button" @@click="openCreate()" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-lg hover:bg-teal-800 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Setting
            </button>
        </div>

        <div id="admin-live-loading" class="hidden" role="status">
            <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-slate-700">
                <svg class="w-4 h-4 text-teal-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <span class="text-sm text-gray-600 dark:text-slate-300">Loading results...</span>
            </div>
        </div>
        <div id="admin-table-region">
            @include('admin.site-settings._table')
        </div>
    </div>

    {{-- Edit/Form Modal --}}
    <x-admin.modal name="site-setting-form" size="lg">
        <form method="POST" :action="formAction">
            @csrf
            <input type="hidden" name="_method" :value="formMethod">
            <input type="hidden" name="_editing" :value="editingId || ''">

            @include('admin.site-settings._form')

            <div class="flex items-center justify-end gap-3 pt-5 mt-6 border-t border-gray-100 dark:border-slate-700">
                <button type="button" @@click="window.dispatchEvent(new CustomEvent('close-modal-site-setting-form'))" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">
                    <span x-text="isEditing ? 'Update Setting' : 'Create Setting'"></span>
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>

@include('components.admin.confirm-dialog', ['name' => 'delete', 'title' => 'Delete Setting?', 'message' => 'Are you sure? This cannot be undone.'])
@endsection

@push('scripts')
<script>
function siteSettingModal() {
    return {
        settings: @js($settingsData),
        isEditing: false,
        editingId: null,
        form: {
            key: '',
            value: '',
            type: 'text',
        },
        formAction: '',
        formMethod: 'PUT',

        openCreate() {
            this.isEditing = false;
            this.editingId = null;
            this.form = {
                key: '',
                value: '',
                type: 'text',
            };
            this.formAction = '{{ route('admin.site-settings.store') }}';
            this.formMethod = 'POST';
            window.dispatchEvent(new CustomEvent('open-modal-site-setting-form', { detail: { title: 'Add Setting' } }));
        },

        openEdit(id) {
            const setting = this.settings.find(s => s.id === id);
            if (!setting) return;
            this.isEditing = true;
            this.editingId = setting.id;
            this.form = {
                key: setting.key || '',
                value: setting.value || '',
                type: setting.type || 'text',
            };
            this.formAction = '/admin/site-settings/' + setting.id;
            this.formMethod = 'PUT';
            window.dispatchEvent(new CustomEvent('open-modal-site-setting-form', { detail: { title: 'Edit Setting: ' + setting.key } }));
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

                const oldKey = @js(old('key', ''));
                const oldValue = @js(old('value', ''));
                const oldType = @js(old('type', ''));

                this.$nextTick(() => {
                    if (oldKey) this.form.key = oldKey;
                    if (oldValue !== null && oldValue !== '') this.form.value = oldValue;
                    if (oldType) this.form.type = oldType;
                });
            }
        },
    };
}
</script>
@endpush
