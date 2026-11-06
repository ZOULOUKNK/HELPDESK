<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /*
        |--------------------------------------------------------------------------
        | Accès réservé au super administrateur
        |--------------------------------------------------------------------------
        */
        $user = $request->user();

        if (!$user || !$user->isSuperAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}