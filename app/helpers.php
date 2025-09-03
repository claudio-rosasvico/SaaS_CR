<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('current_org_id')) {
    /**
     * Devuelve el ID de la organización “activa”.
     *
     * Prioridad:
     * 1) Usuario autenticado -> users.current_organization_id
     * 2) Contexto “tenant” seteado en runtime (p. ej., desde Telegram/WhatsApp)
     * 3) Fallback de .env: ORG_DEFAULT_ID (por defecto 1)
     */
    function current_org_id(?int $fallback = null): ?int
    {
        // 1) Usuario logueado
        if (Auth::check() && Auth::user()->current_organization_id) {
            return (int) Auth::user()->current_organization_id;
        }

        // 2) Contexto seteado a mano (útil en controladores de canales)
        if (app()->bound('tenant.org_id')) {
            return (int) app('tenant.org_id');
        }

        // 3) Fallback
        $envFallback = (int) env('ORG_DEFAULT_ID', 1);
        return $fallback !== null ? $fallback : $envFallback;
    }
}

if (! function_exists('set_tenant_org_id')) {
    /**
     * Setea el contexto de organización “en caliente” para esta request/proceso.
     * Útil en controladores de Telegram/WhatsApp antes de crear/consultar registros.
     */
    function set_tenant_org_id(int $orgId): void
    {
        app()->instance('tenant.org_id', $orgId);
    }
}

if (! function_exists('current_org')) {
    /**
     * Retorna la organización actual (o null si no existe).
     * Evitá usarlo en hot-paths si no tenés cache; para la mayoría de casos basta current_org_id().
     */
    function current_org(): ?\App\Models\Organization
    {
        $id = current_org_id();
        return $id ? \App\Models\Organization::find($id) : null;
    }
}
