<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Middleware paramétré : role:manager,admin.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if($user === null, 401, 'Non authentifié.');

        abort_unless(
            in_array($user->role->value, $roles, true),
            403,
            'Votre rôle ne vous autorise pas à effectuer cette opération.'
        );

        return $next($request);
    }
}
