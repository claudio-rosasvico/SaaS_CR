<?php

use App\Models\Bot;

/**
 * Fuerza una organización “temporal” (útil en procesos sin sesión, ej. Telegram/CLI).
 */
function with_org(int $orgId, callable $fn)
{
    $prev = app()->has('forced_org') ? app('forced_org') : null;
    app()->instance('forced_org', $orgId);
    try {
        return $fn();
    } finally {
        app()->forgetInstance('forced_org');
        if ($prev !== null) app()->instance('forced_org', $prev);
    }
}

/**
 * Devuelve el org actual.
 * Orden recomendado:
 * 1) forced_org (CLI/bots) → 2) user autenticado → 3) session
 */
if (! function_exists('current_org_id')) {
    function current_org_id(): ?int
    {
        if (app()->has('forced_org')) {
            return (int) app('forced_org');
        }
        if (auth()->check()) {
            return auth()->user()->current_organization_id;
        }
        return session('current_organization_id');
    }
}

/**
 * Setea el org “en caliente” (por si querés hacerlo manualmente en controladores).
 */
if (! function_exists('set_tenant_org_id')) {
    function set_tenant_org_id(int $orgId): void
    {
        app()->instance('tenant.org_id', $orgId);
    }
}

/**
 * Devuelve instancia de Organization (si querés).
 */
if (! function_exists('current_org')) {
    function current_org(): ?\App\Models\Organization
    {
        $id = current_org_id();
        return $id ? \App\Models\Organization::find($id) : null;
    }
}

/**
 * Garantiza que exista un bot default por canal/org y lo devuelve.
 * Además setea defaults **seguros** en config, incluyendo presentación.
 */
if (! function_exists('ensure_default_bot')) {
    function ensure_default_bot(?string $channel = 'web', ?int $orgId = null): \App\Models\Bot
    {
        $orgId = $orgId ?: current_org_id();

        $bot = Bot::where('organization_id', $orgId)
            ->where('channel', $channel)
            ->where('is_default', true)
            ->first();

        if ($bot) {
            return $bot;
        }

        return Bot::firstOrCreate(
            [
                'organization_id' => $orgId,
                'channel'         => $channel,
                'name'            => ucfirst($channel) . ' default',
            ],
            [
                'is_default' => true,
                'config'     => [
                    'language'       => 'es',
                    'citations'      => false,
                    'max_tokens'     => 400,
                    'temperature'    => 0.2,
                    'system_prompt'  => 'Asistente útil que usa SOLO el contexto. Si no está, dilo.',
                    'retrieval_mode' => env('RETRIEVAL_MODE', 'semantic'),
                    'presentation'   => [
                        'hide_urls'                => true,
                        'hide_file_names'          => true,
                        'max_fallback_suggestions' => 3,
                        'aliases' => [
                            'domains' => [],
                            'files'   => [],
                        ],
                        'closing_line' => null,
                    ],
                ],
            ]
        );
    }
}

/**
 * Token de Telegram de una org (desde ChannelIntegration).
 */
if (! function_exists('telegram_token_for_org')) {
    function telegram_token_for_org(int $orgId): ?string
    {
        $ci = \App\Models\ChannelIntegration::where('organization_id', $orgId)
            ->where('channel', 'telegram')
            ->where('enabled', true)
            ->first();

        return $ci ? (string) data_get($ci->config, 'token', null) : null;
    }
}

if (! function_exists('bot_by_key')) {
    function bot_by_key(string $key): ?\App\Models\Bot
    {
        return \App\Models\Bot::where('public_key', $key)
            ->where('is_embeddable', true)->first();
    }
}

if (! function_exists('bot_by_public_key')) {
    function bot_by_public_key(string $publicKey): ?\App\Models\Bot {
        /** @var \App\Models\Bot|null $bot */
        $bot = \App\Models\Bot::where('channel', 'web')
            ->where('public_key', $publicKey)
            ->first();

        return $bot;
    }
}

