<?php

namespace App\Http\Controllers\Embed;

use Illuminate\Http\Request;
use App\Models\Bot;
use App\Http\Controllers\Controller;

class WidgetPageController extends Controller
{
    public function __invoke(string $publicKey)
    {
        $bot = Bot::where('public_key', $publicKey)->first();
        abort_unless($bot && $bot->channel === 'web', 404);

        // inyectá org en contexto por si algo lo requiere
        with_org($bot->organization_id, function () {});

        $cfg  = (array)($bot->config ?? []);
        $pres = (array)($cfg['presentation'] ?? []);
        $theme = (array)($bot->embed_theme ?? []);

        return response()
            ->view('embed.widget', [
                'botName'       => $bot->name,
                'publicKey'     => $publicKey,
                'welcomeText'   => (string)($pres['welcome_text'] ?? '¡Hola! ¿En qué te ayudo?'),
                'suggested'     => (array) ($pres['suggested'] ?? []),
                'primary'       => (string)($theme['primary'] ?? '#2563eb'),
                'rounded'       => (bool)  ($theme['rounded'] ?? true),
            ])
            ->header('X-Frame-Options', 'ALLOWALL'); // permitir embeber
    }
}
