<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            // ya viene de la DB
            session(['current_organization_id' => auth()->user()->current_organization_id]);
        } elseif ($request->has('org')) {
            // útil para tests o links con ?org=5
            session(['current_organization_id' => (int) $request->get('org')]);
        }

        return $next($request);
    }
}
