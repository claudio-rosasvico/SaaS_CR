<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ChatService;

class EmbedChatController extends Controller
{
    public function chat(Request $req, ChatService $chat)
    {
        // aceptar ambos nombres para compatibilidad
        $publicKey = (string) ($req->input('public_key') ?? $req->input('bot_key') ?? '');
        $text      = trim((string) $req->input('q', ''));
        $convId    = $req->input('conversation_id');

        if ($publicKey === '' || $text === '' || mb_strlen($text) > 2000) {
            return response()->json(['error' => 'Parámetros inválidos'], 422);
        }

        $bot = bot_by_public_key($publicKey);
        if (!$bot) {
            return response()->json(['error' => 'Bot no encontrado'], 404);
        }

        return with_org($bot->organization_id, function () use ($chat, $convId, $text) {
            $res = $chat->handle($convId, $text, 'web');
            return response()->json([
                'conversation_id' => $res['conversation_id'],
                'messages'        => $res['messages'],
                'answer'          => $res['messages'][1]['content'] ?? '',
            ]);
        });
    }
}
