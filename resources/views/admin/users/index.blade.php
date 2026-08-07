@extends('admin.layouts.app')

@php
$showUserModal = $errors->hasAny(['name', 'email', 'password', 'role']);
$editingId = old('_editing', 0);
$editingUser = $editingId ? \App\Models\User::find($editingId) : null;
$editingUserJson = $editingUser ? $editingUser->only('id', 'name', 'email', 'role') : null;
$usersData = $users->map(fn ($u) => [
    'id' => $u->id,
    'name' => $u->name,
    'email' => $u->email,
    'role' => $u->role,
    'joined' => $u->created_at->format('M d, Y'),
])->values();
@endphp

@section('title', 'Users')
@section('header', 'Users')
@section('description', 'Manage admin users')

@section('breadcrumb')
    <nav class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700 transition-colors dark:hover:text-teal-300">Dashboard</a>
        <span>/</span>
        <span class="text-gray-700 font-medium dark:text-slate-200">Users</span>
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

            <div class="flex items-center justify-end gap-3 pt-5 mt-6 border-t border-gray-100 dark:border-slate-700">
                <button type="button" @@click="window.dispatchEvent(new CustomEvent('close-modal-user-form'))" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors dark:bg-slate-800 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-teal-700 rounded-lg hover:bg-teal-700 transition-colors shadow-sm">
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
        allUsers: @js($usersData),
        authId: @js(auth()->id()),
        search: '',
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

        get filteredUsers() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.allUsers;
            return this.allUsers.filter(u =>
                (u.name || '').toLowerCase().includes(q) ||
                (u.email || '').toLowerCase().includes(q)
            );
        },

        get adminCount() {
            return this.filteredUsers.filter(u => u.role === 'admin' || u.role === 'super_admin').length;
        },

        get staffCount() {
            return this.filteredUsers.filter(u => u.role === 'staff').length;
        },

        initials(name) {
            const parts = String(name || '').trim().split(/\s+/);
            const a = (parts[0] || '')[0] || '';
            const b = parts.length > 1 ? (parts[1] || '')[0] || '' : (parts[0] || '')[1] || '';
            return (a + b).toUpperCase();
        },

        roleClass(role) {
            return role === 'super_admin' ? 'bg-red-500' : role === 'admin' ? 'bg-teal-500' : 'bg-gray-400';
        },

        roleBadgeClass(role) {
            return role === 'super_admin'
                ? 'bg-red-50 text-red-700 ring-red-600/20'
                : role === 'admin'
                    ? 'bg-amber-50 text-amber-700 ring-amber-600/20'
                    : 'bg-gray-50 text-gray-600 ring-gray-500/20';
        },

        roleLabel(role) {
            return role === 'super_admin' ? 'Super Admin' : role === 'admin' ? 'Admin' : 'Staff';
        },

        clearSearch() {
            this.search = '';
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
        },
    };
}
</script>
