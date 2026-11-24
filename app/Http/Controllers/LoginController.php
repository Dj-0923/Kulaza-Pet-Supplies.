<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim((string) $credentials['username']);
        $loginField = str_contains($identifier, '@') ? 'email' : 'username';
        if ($loginField === 'email') {
            $identifier = strtolower($identifier);
        }
        $attemptCredentials = [
            $loginField => $identifier,
            'password' => $credentials['password'],
        ];

        if (! Auth::attempt($attemptCredentials)) {
            throw ValidationException::withMessages([
                'username' => 'The username, Gmail address, or password is incorrect.',
            ]);
        }

        if ($request->user()->must_set_password) {
            Auth::logout();

            throw ValidationException::withMessages([
                'username' => 'Complete account setup using the link sent to your Gmail address before logging in.',
            ]);
        }

        if (! $request->user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'username' => 'Your account is inactive. Contact an administrator.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->flash('login_animation', true);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
