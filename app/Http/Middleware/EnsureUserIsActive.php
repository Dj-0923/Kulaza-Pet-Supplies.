<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $account = $user ? User::whereKey($user->getKey())->first(['is_active', 'must_set_password']) : null;

        if (! $account || $account->must_set_password || ! $account->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Your account is inactive. Contact an administrator.',
            ]);
        }

        return $next($request);
    }
}
