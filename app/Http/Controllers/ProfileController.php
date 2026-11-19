<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return redirect()->route('profile')->with('status', 'Password changed successfully.');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'alpha_dash',
                'max:50',
                Rule::unique('users', 'username')->ignore($user),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'current_password' => [
                Rule::requiredIf(fn () => $request->input('email') !== $user->email),
                'current_password',
            ],
        ]);

        unset($validated['current_password']);
        $user->update($validated);

        return redirect()->route('profile')->with('status', 'Profile updated successfully.');
    }
}
