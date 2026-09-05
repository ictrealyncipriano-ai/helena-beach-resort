<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AdminPasswordReset;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            $token = Password::broker()->createToken($user);
            $resetUrl = route('admin.password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);

            Mail::to($user->email)->queue(new AdminPasswordReset($user->name, $resetUrl));
        }

        // Return the same message whether or not the account exists so the
        // endpoint cannot be used to probe which email addresses are admins.
        return back()->with('status', 'If an account exists for that email, a password reset link has been sent.');
    }
}
