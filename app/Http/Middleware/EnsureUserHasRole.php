<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $role = $user ? User::whereKey($user->getKey())->value('role') : null;

        abort_unless(in_array($role, $roles, true), 403);

        return $next($request);
    }
}
