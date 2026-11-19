<?php

namespace App\Http\Controllers;

use App\Models\MailSettings;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $settings = MailSettings::current();
        if ($settings) {
            $settings->configureMailer();
        }

        if ($settings) {
            Password::sendResetLink($request->only('email'));
        }

        $message = $settings
            ? 'If an account uses that email address, a password reset link has been sent.'
            : 'Email recovery is not configured yet. Please contact an administrator.';

        return back()->with('status', $message);
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Your password has been reset. You can now log in.');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
