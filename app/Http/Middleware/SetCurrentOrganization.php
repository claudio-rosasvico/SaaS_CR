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
            $user = auth()->user();

            // Si no tiene org actual, usa la primera o crea una propia
            if (!$user->current_organization_id) {
                $org = $user->organizations()->first();
                if (!$org) {
                    $org = \App\Models\Organization::create(['name' => $user->name.' Org']);
                    $user->organizations()->attach($org->id, ['role'=>'owner']);
                }
                $user->current_organization_id = $org->id;
                $user->save();
            }
        }
        return $next($request);
    }
}
