@extends('admin.layouts.app')

@php
$showUserModal = $errors->hasAny(['name', 'email', 'password', 'role']);
$editingId = old('_editing', 0);
$editingUser = $editingId ? \App\Models\User::find($editingId) : null;
$editingUserJson = $editingUser ? $editingUser->only('id', 'name', 'email', 'role') : null;
@endphp

@section('title', 'Users')
@section('header', 'Users')
@section('description', 'Manage admin users')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-600 transition-colors">Dashboard</a>
        <span>/</span>
        <span class="text-gray-700 font-medium">Users</span>
    </nav>
@endsection

@section('content')
<div x-data="userForm()" class="space-y-6">
    <div id="users-results">
        @include('admin.users._results')
    </div>

    {{-- User Form Modal --}}
    <x-admin.modal name="user-form" size="lg">
        <form method="POST" :action="formAction" x-ref="form">
            @csrf
            <input type="hidden" name="_method" :value="formMethod" x-show="formMethod !== 'POST'">
            <input type="hidden" name="_editing" :value="editingId || ''">

            @include('admin.users._form')

            <div class="flex items-center justify-end gap-3 pt-5 mt-6 border-t border-gray-100">
                <button type="button" @@click="window.dispatchEvent(new CustomEvent('close-modal-user-form'))" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">
                    <span x-text="isEditing ? 'Update User' : 'Create User'"></span>
                </button>
            </div>
        </form>
    </x-admin.modal>
</div>

@include('admin.components.confirm-dialog', ['name' => 'delete', 'title' => 'Delete User?', 'message' => 'Are you sure you want to delete this user? This action cannot be undone.'])
@endsection

<script>
window.userForm = function() {
    return {
        isEditing: false,
        editingId: null,
        formAction: '{{ route('admin.users.store') }}',
        formMethod: 'POST',
        form: {
            name: '',
            email: '',
            password: '',
            role: 'admin',
        },

        openCreate() {
            this.isEditing = false;
            this.editingId = null;
            this.formAction = '{{ route('admin.users.store') }}';
            this.formMethod = 'POST';
            this.form = { name: '', email: '', password: '', role: 'admin' };
            window.dispatchEvent(new CustomEvent('open-modal-user-form', { detail: { title: 'Create User' } }));
        },

        openEdit(user) {
            this.isEditing = true;
            this.editingId = user.id;
            this.formAction = '/admin/users/' + user.id;
            this.formMethod = 'PUT';
            this.form = { name: user.name, email: user.email, password: '', role: user.role };
            window.dispatchEvent(new CustomEvent('open-modal-user-form', { detail: { title: 'Edit User' } }));
        },

        performSearch(q) {
            clearTimeout(this._searchTimer);
            this._searchTimer = setTimeout(() => this._runSearch(q), 400);
        },

        async _runSearch(q) {
            const url = '/admin/users?search=' + encodeURIComponent(q || '');
            try {
                const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!resp.ok) return;
                const container = document.getElementById('users-results');
                container.innerHTML = await resp.text();
                Alpine.initTree(container);
                history.replaceState(null, '', url);
            } catch (e) {
                // keep previous results if the request fails
            }
        },

        init() {
            const showModal = @js($showUserModal);
            const editingUser = @js($editingUserJson);
            const oldName = @js(old('name', ''));
            const oldEmail = @js(old('email', ''));
            const oldRole = @js(old('role', 'admin'));

            if (showModal) {
                if (editingUser) {
                    this.openEdit(editingUser);
                    this.$nextTick(() => {
                        if (oldName) this.form.name = oldName;
                        if (oldEmail) this.form.email = oldEmail;
                        this.form.role = oldRole;
                    });
                } else {
                    this.openCreate();
                    this.$nextTick(() => {
                        if (oldName) this.form.name = oldName;
                        if (oldEmail) this.form.email = oldEmail;
                        this.form.role = oldRole;
                    });
                }
            }

            document.addEventListener('input', (e) => {
                if (e.target.id !== 'user-search') return;
                this.performSearch(e.target.value);
            });
        },
    };
}
</script>
