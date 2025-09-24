<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmbedController extends Controller
{
    public function show(string $key)
    {
        $bot = bot_by_key($key);
        abort_unless($bot, 404);

        // Fijamos el contexto de organización SOLO en esta request:
        return with_org($bot->organization_id, function () use ($bot) {
            // Podés pasar opciones de tema desde $bot->embed_theme
            return view('embed.chat', [
                'bot'  => $bot,
                'key'  => $bot->public_key,
                'theme'=> $bot->embed_theme ?? [],
            ]);
        });
    }
}
