<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class StaffSetupController extends Controller
{
    public function edit(Request $request, string $token)
    {
        $email = strtolower((string) $request->query('email'));
        $user = User::where('email', $email)
            ->where('role', 'staff')
            ->where('must_set_password', true)
            ->first();

        if (! $user || ! Password::tokenExists($user, $token)) {
            return redirect()->route('login')->withErrors([
                'username' => 'This account setup link is invalid or has expired. Ask an administrator to resend it.',
            ]);
        }

        return view('auth.staff-setup', compact('email', 'token', 'user'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'regex:/^[^@\s]+@gmail\.com$/i'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower($validated['email']);
        $user = User::where('email', $email)
            ->where('role', 'staff')
            ->where('must_set_password', true)
            ->first();

        if (! $user || ! Password::tokenExists($user, $validated['token'])) {
            return back()->withErrors([
                'email' => 'This account setup link is invalid or has expired. Ask an administrator to resend it.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_set_password' => false,
            'email_verified_at' => now(),
            'remember_token' => Str::random(60),
            'is_active' => true,
        ])->save();

        Password::deleteToken($user);
        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Your password is set. You can now log in with your username.');
    }
}
