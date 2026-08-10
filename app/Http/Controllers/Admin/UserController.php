<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->get();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User]);
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role' => 'required|in:super_admin,admin,staff',
        ]);

        // Only an existing super_admin may create another super_admin.
        if ($data['role'] === 'super_admin' && auth()->user()->role !== User::ROLE_SUPER_ADMIN) {
            return back()->withInput()->withErrors([
                'role' => 'Only a super administrator can create a super administrator account.',
            ]);
        }

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        $logger->record('user.created', $user, "User {$user->name} created.", [
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user, ActivityLogger $logger)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
            'role' => 'required|in:super_admin,admin,staff',
        ]);

        // A user may never change their own role (prevents self-escalation).
        if ($user->id === auth()->id() && $data['role'] !== $user->role) {
            return back()->withInput()->withErrors([
                'role' => 'You cannot change your own role.',
            ]);
        }

        // Only a super_admin may assign the super_admin role...
        if ($data['role'] === User::ROLE_SUPER_ADMIN && auth()->user()->role !== User::ROLE_SUPER_ADMIN) {
            return back()->withInput()->withErrors([
                'role' => 'Only a super administrator can assign the super administrator role.',
            ]);
        }

        // ...and only a super_admin may modify a super_admin's record.
        if ($user->role === User::ROLE_SUPER_ADMIN && auth()->user()->role !== User::ROLE_SUPER_ADMIN) {
            return back()->withInput()->withErrors([
                'role' => 'Only a super administrator can modify a super administrator account.',
            ]);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        $logger->record('user.updated', $user, "User {$user->name} updated.", [
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user, ActivityLogger $logger)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Only a super_admin may delete another super_admin's account.
        if ($user->role === User::ROLE_SUPER_ADMIN && auth()->user()->role !== User::ROLE_SUPER_ADMIN) {
            return back()->with('error', 'Only a super administrator can delete a super administrator account.');
        }

        $user->delete();

        $logger->record('user.deleted', $user, "User {$user->name} deleted.", [
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
