<?php

use Illuminate\Support\Facades\Auth;
use App\Models\Bot;

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


if (! function_exists('ensure_default_bot')) {
    function ensure_default_bot(): Bot
    {
        $orgId = current_org_id();
        $bot = Bot::where('organization_id', $orgId)->orderBy('id')->first();
        if ($bot) return $bot;

        // crear uno por defecto
        $newBot = Bot::create([
            'organization_id' => $orgId,
            'name'   => 'Demo Web',
            'channel' => 'web',
            'config' => [
                'system_prompt' => "Eres un asistente de soporte que responde en español, claro y conciso. Prioriza la información de las FUENTES proporcionadas. Si falta información en el contexto, dilo explícitamente y sugiere qué documento subir.",
                'temperature'   => 0.2,
                'max_tokens'    => 400,
                'retrieval_mode' => env('RETRIEVAL_MODE', 'semantic'),
                'citations'     => false,  // si luego querés forzar que cite
                'language'      => 'es',
            ],
        ]);
        return $newBot;
    }
}

if (! function_exists('ensure_default_bot')) {
    function ensure_default_bot(string $channel = 'web', ?int $orgId = null): \App\Models\Bot
    {
        $orgId = $orgId ?? current_org_id();
        $key = "bot:{$orgId}:{$channel}";

        return cache()->remember($key, 30, function () use ($orgId, $channel) {
            return \App\Models\Bot::firstOrCreate(
                ['organization_id' => $orgId, 'channel' => $channel],
                [
                    'system_prompt' => "Eres un asistente de soporte. Responde SOLO con el CONTEXTO.",
                    'temperature'   => (float) env('LLM_TEMPERATURE', 0.2),
                    'max_tokens'    => (int)   env('LLM_MAX_TOKENS', 500),
                ]
            );
        });
    }
}

function telegram_token_for_org(int $orgId): ?string
{
    $ci = \App\Models\ChannelIntegration::where('organization_id', $orgId)
        ->where('channel', 'telegram')->where('enabled', true)->first();
    return $ci->config['token'] ?? null;
}
