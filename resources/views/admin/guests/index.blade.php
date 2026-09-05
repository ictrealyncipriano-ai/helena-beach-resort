@extends('admin.layouts.app')

@php
$showEditModal = $errors->hasAny(['name', 'email', 'phone', 'notes']);
$editingId = old('_editing', 0);
$editingData = null;
if ($editingId) {
    foreach ($guestsData as $data) {
        if ((int) $data['id'] === (int) $editingId) {
            $editingData = $data;
            break;
        }
    }
}
@endphp

@section('title', 'Guests')
@section('header', 'Guests')
@section('description', 'Manage guest profiles')

@section('breadcrumb')
    <x-admin.breadcrumb :items="[['label' => 'Guests']]" />
@endsection

@section('content')
<div x-data="guestModal()" class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-slate-700">
            <form method="GET" class="flex gap-3 flex-1 max-w-md">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, phone..." class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-400 dark:bg-slate-800 dark:border-slate-600 dark:text-white dark:placeholder-slate-400 dark:focus:border-teal-500 dark:focus:ring-teal-500/20">
                </div>
                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors">Search</button>
                @if(request('search'))
                    <a href="{{ route('admin.guests.index') }}" class="px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Clear</a>
                @endif
            </form>
        </div>

        @if($guests->isEmpty())
            <x-admin.empty-state title="No guests" message="Guest profiles are automatically created from inquiries." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-50 text-xs text-gray-500 uppercase tracking-wider dark:border-slate-700 dark:text-slate-400">
                            <th class="text-left px-5 py-3 font-medium">Name</th>
                            <th class="text-left px-5 py-3 font-medium">Email</th>
                            <th class="text-left px-5 py-3 font-medium hidden sm:table-cell">Phone</th>
                            <th class="text-center px-5 py-3 font-medium">Stays</th>
                            <th class="text-left px-5 py-3 font-medium">Last Stay</th>
                            <th class="text-left px-5 py-3 font-medium hidden md:table-cell">Created</th>
                            <th class="text-right px-5 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @foreach($guests as $guest)
                        <tr class="hover:bg-gray-50 transition-colors dark:hover:bg-slate-700/40">
                            <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $guest->name }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-slate-400">{{ $guest->email }}</td>
                            <td class="px-5 py-3 text-gray-500 hidden sm:table-cell dark:text-slate-400">{{ $guest->phone ?? '—' }}</td>
                            <td class="px-5 py-3 text-center">@include('components.admin.badge', ['type' => 'primary', 'slot' => $guest->inquiries_count])</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-slate-300">{{ $guest->last_stay_at?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 hidden md:table-cell dark:text-slate-400">{{ $guest->created_at->format('M d, Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @@click="openView({{ $guest->id }})" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-blue-400 dark:hover:bg-blue-500/10" aria-label="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </button>
                                    <button type="button" @@click="openEdit({{ $guest->id }})" class="p-1.5 text-gray-500 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-teal-300 dark:hover:bg-teal-900/30" aria-label="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    <button type="button" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-500/10" aria-label="Delete" @@click="$dispatch('open-confirm-delete', { url: '{{ route('admin.guests.destroy', $guest) }}', method: 'DELETE' })">
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
                <x-admin.pagination :paginator="$guests" />
            </div>
        @endif
    </div>

    {{-- View Modal --}}
    <x-admin.modal name="guest-view" size="xl">
        @include('admin.guests._view')

        <div class="flex flex-wrap items-center justify-end gap-2 pt-5 mt-6 border-t border-gray-100 dark:border-slate-700">
            <a x-bind:href="'/admin/guests/' + view.id" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                Open Profile Page
            </a>
            <button type="button"
                @@click="openEdit(view.id)"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                Edit
            </button>
        </div>
    </x-admin.modal>

    {{-- Edit/Form Modal --}}
    <x-admin.modal name="guest-form" size="lg">
        <form method="POST" :action="formAction">
            @csrf
            <input type="hidden" name="_method" :value="formMethod">
            <input type="hidden" name="_editing" :value="editingId || ''">

            @include('admin.guests._form')

            <div class="flex items-center justify-end gap-3 pt-5 mt-6 border-t border-gray-100 dark:border-slate-700">
                <button type="button" @@click="window.dispatchEvent(new CustomEvent('close-modal-guest-form'))" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-800 transition-colors shadow-sm">Update Guest</button>
            </div>
        </form>
    </x-admin.modal>
</div>

<x-admin.confirm-dialog name="delete" title="Delete Guest?" message="Are you sure? This cannot be undone." />
@endsection

<script>
window.guestModal = function() {
    return {
        guests: @js($guestsData),
        view: {},
        isEditing: false,
        editingId: null,
        form: {
            name: '',
            email: '',
            phone: '',
            notes: '',
        },
        formAction: '',
        formMethod: 'PUT',

        formatAmount(value) {
            const n = Number(value || 0);
            return '₱ ' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        openView(id) {
            const guest = this.guests.find(g => g.id === id);
            if (!guest) return;
            this.view = guest;
            window.dispatchEvent(new CustomEvent('open-modal-guest-view', { detail: { title: guest.name } }));
        },

        openEdit(id) {
            const guest = this.guests.find(g => g.id === id);
            if (!guest) return;
            this.isEditing = true;
            this.editingId = guest.id;
            this.form = {
                name: guest.name || '',
                email: guest.email || '',
                phone: guest.phone || '',
                notes: guest.notes || '',
            };
            this.formAction = '/admin/guests/' + guest.id;
            this.formMethod = 'PUT';
            window.dispatchEvent(new CustomEvent('close-modal-guest-view'));
            window.dispatchEvent(new CustomEvent('open-modal-guest-form', { detail: { title: 'Edit Guest: ' + guest.name } }));
        },

        statusLabel(status) {
            return status ? status.charAt(0).toUpperCase() + status.slice(1) : '—';
        },

        statusBadgeClass(status) {
            return status === 'confirmed' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300 ring-emerald-500/30'
                : status === 'cancelled' ? 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300 ring-red-500/30'
                : status === 'expired' ? 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-slate-500/10 dark:text-slate-300'
                : 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300';
        },

        typeBadgeClass(type) {
            return type === 'day_tour' ? 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300'
                : type === 'overnight' ? 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300'
                : 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-slate-500/10 dark:text-slate-300';
        },

        paymentBadgeClass(key) {
            return key === 'paid' ? 'bg-teal-50 text-teal-700 ring-teal-600/20 dark:bg-teal-500/10 dark:text-teal-300'
                : key === 'refunded' || key === 'failed' ? 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300'
                : 'bg-gray-50 text-gray-600 ring-gray-500/20 dark:bg-slate-500/10 dark:text-slate-300';
        },

        init() {
            const showModal = @js($showEditModal);
            const editingId = @js($editingData ? $editingData['id'] : 0);
            const oldName = @js(old('name', ''));
            const oldEmail = @js(old('email', ''));
            const oldPhone = @js(old('phone', ''));
            const oldNotes = @js(old('notes', ''));

            if (showModal && editingId) {
                this.openEdit(Number(editingId));
                this.$nextTick(() => {
                    if (oldName) this.form.name = oldName;
                    if (oldEmail) this.form.email = oldEmail;
                    if (oldPhone) this.form.phone = oldPhone;
                    this.form.notes = oldNotes;
                });
            }
        },
    };
}
</script>
