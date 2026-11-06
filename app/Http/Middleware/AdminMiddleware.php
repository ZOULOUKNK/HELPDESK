<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /*
        |--------------------------------------------------------------------------
        | Accès administration
        |--------------------------------------------------------------------------
        | Le super administrateur doit aussi passer ici, sinon il est bloqué
        | sur les routes protégées par le middleware admin.
        */
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}