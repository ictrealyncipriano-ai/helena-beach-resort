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
            <form method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search settings by key or value..." x-data="liveSearchState()" x-model="search" @input.debounce.350ms="goSearch()" class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-700 transition-colors">Filter</button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.site-settings.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Clear</a>
                @endif
            </form>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ $settings->total() }} settings</p>
            <button type="button" @@click="openCreate()" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-700 text-white text-sm font-medium rounded-lg hover:bg-teal-700 transition-colors shadow-sm whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Setting
            </button>
        </div>

        @if($settings->isEmpty())
            @include('admin.components.empty-state', [
                'title' => 'No settings',
                'message' => 'Add your first site setting.',
                'actionClick' => 'openCreate()',
                'actionLabel' => 'Add Setting',
            ])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                            <th class="text-left px-5 py-3 font-medium">Key</th>
                            <th class="text-left px-5 py-3 font-medium">Value</th>
                            <th class="text-left px-5 py-3 font-medium">Type</th>
                            <th class="text-right px-5 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @foreach($settings as $setting)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                            <td class="px-5 py-3 font-medium text-gray-500 font-mono text-xs dark:text-slate-400">{{ $setting->key }}</td>
                            <td class="px-5 py-3 text-gray-700 max-w-md truncate dark:text-slate-300">{{ Str::limit($setting->value, 60) }}</td>
                            <td class="px-5 py-3">@include('admin.components.badge', ['type' => $setting->type === 'image' ? 'info' : ($setting->type === 'textarea' ? 'warning' : 'gray'), 'slot' => ucfirst($setting->type)])</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @@click="openEdit({{ $setting->id }})" class="p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    <button type="button" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-500/10" aria-label="Delete"
                                        @@click="$dispatch('open-confirm-delete', { url: '{{ route('admin.site-settings.destroy', $setting) }}', method: 'DELETE' })">
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
                @include('admin.components.pagination', ['paginator' => $settings])
            </div>
        @endif
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
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">
                    <span x-text="isEditing ? 'Update Setting' : 'Create Setting'"></span>
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>

@include('admin.components.confirm-dialog', ['name' => 'delete', 'title' => 'Delete Setting?', 'message' => 'Are you sure? This cannot be undone.'])
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
