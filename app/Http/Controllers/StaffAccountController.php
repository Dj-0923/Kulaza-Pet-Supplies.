<?php

namespace App\Http\Controllers;

use App\Mail\StaffAccountSetupMail;
use App\Models\MailSettings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class StaffAccountController extends Controller
{
    public function index()
    {
        $staffAccounts = User::where('role', 'staff')->orderBy('name')->get();

        return view('staff-accounts', compact('staffAccounts'));
    }

    public function statuses(): JsonResponse
    {
        $staffAccounts = User::where('role', 'staff')
            ->orderBy('name')
            ->get(['id', 'is_active', 'must_set_password']);

        return response()->json($staffAccounts);
    }

    public function store(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'middle_name' => trim((string) $request->input('middle_name')) ?: null,
        ]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'regex:/^[^@\s]+@gmail\.com$/i', 'max:255', 'unique:users,email'],
        ]);

        $settings = MailSettings::current();
        if (! $settings) {
            return back()->withInput()->withErrors([
                'email' => 'Configure Gmail sending in Profile before creating staff accounts.',
            ]);
        }

        $staffAccount = User::create([
            'name' => collect([
                $validated['first_name'],
                $validated['middle_name'],
                $validated['last_name'],
            ])->filter()->implode(' '),
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Str::random(64),
            'role' => 'staff',
            'is_active' => false,
            'must_set_password' => true,
        ]);

        if (! $this->sendSetupEmail($staffAccount, $settings)) {
            return redirect()->route('staff')->withErrors([
                'email' => 'The staff account was created, but the setup email could not be sent. Use Resend Setup Email to try again.',
            ]);
        }

        return redirect()->route('staff')->with('status', 'Staff account created. A setup email was sent to '.$staffAccount->email.'.');
    }

    public function updateStatus(Request $request, User $user)
    {
        if ($user->role !== 'staff') {
            abort(404);
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->must_set_password && $validated['is_active']) {
            return back()->withErrors([
                'is_active' => 'The staff member must complete account setup before the account can be activated.',
            ]);
        }

        $user->update(['is_active' => $validated['is_active']]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $user->id,
                'is_active' => $user->is_active,
                'must_set_password' => $user->must_set_password,
            ]);
        }

        return redirect()->route('staff')->with('status', 'Staff work status updated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        if ($user->role !== 'staff') {
            abort(404);
        }

        $settings = MailSettings::current();
        if (! $settings) {
            return back()->withErrors([
                'email' => 'Configure Gmail sending in Profile before sending password reset emails.',
            ]);
        }

        $settings->configureMailer();

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (TransportExceptionInterface $exception) {
            Log::error('Staff password reset email could not be delivered.', [
                'user_id' => $user->getKey(),
                'exception' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'The password reset email could not be sent. Check the Gmail sending settings and try again.',
            ]);
        }

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors([
                'email' => 'A password reset email could not be sent to this staff account.',
            ]);
        }

        return redirect()->route('staff')->with(
            'status',
            'A password reset link was sent to '.$user->email.'.'
        );
    }

    public function resendSetupEmail(User $user)
    {
        if ($user->role !== 'staff' || ! $user->must_set_password) {
            abort(404);
        }

        $settings = MailSettings::current();
        if (! $settings) {
            return back()->withErrors([
                'email' => 'Configure Gmail sending in Profile before resending account setup emails.',
            ]);
        }

        if (! $this->sendSetupEmail($user, $settings)) {
            return back()->withErrors([
                'email' => 'The setup email could not be sent. Check the Gmail sending settings and try again.',
            ]);
        }

        return redirect()->route('staff')->with('status', 'A new setup email was sent to '.$user->email.'. Any previous setup link is no longer valid.');
    }

    public function destroy(User $user)
    {
        if ($user->role !== 'staff') {
            abort(404);
        }

        Password::deleteToken($user);
        $user->delete();

        return redirect()->route('staff')->with('status', 'Staff account permanently deleted.');
    }

    private function sendSetupEmail(User $user, MailSettings $settings): bool
    {
        Password::deleteToken($user);
        $token = Password::createToken($user);
        $settings->configureMailer();

        try {
            Mail::to($user->email)->send(new StaffAccountSetupMail($user, $token));
        } catch (TransportExceptionInterface $exception) {
            Log::error('Staff account setup email could not be delivered.', [
                'user_id' => $user->getKey(),
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
