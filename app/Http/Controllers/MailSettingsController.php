<?php

namespace App\Http\Controllers;

use App\Models\MailSettings;
use Illuminate\Http\Request;

class MailSettingsController extends Controller
{
    public function update(Request $request)
    {
        $request->merge([
            'smtp_password' => preg_replace('/\s+/', '', (string) $request->input('smtp_password')),
        ]);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'smtp_username' => ['required', 'email', 'max:255'],
            'smtp_password' => ['required', 'string', 'min:16', 'max:255'],
        ]);

        $settings = MailSettings::query()->firstOrNew(['id' => 1]);
        $settings->fill([
            'smtp_username' => strtolower($validated['smtp_username']),
            'smtp_password' => $validated['smtp_password'],
        ]);
        $settings->save();

        return redirect()->route('profile')->with('status', 'Gmail sending settings saved.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        MailSettings::query()->delete();

        return redirect()->route('profile')->with('status', 'Gmail sending settings removed. Password reset emails are disabled until you configure Gmail again.');
    }
}
