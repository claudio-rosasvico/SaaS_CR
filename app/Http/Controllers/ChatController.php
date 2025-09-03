<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ChatService;

class ChatController extends Controller
{
    private ChatService $chat;

    public function __construct(ChatService $chat)
    {
        $this->chat = $chat;
    }

    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['nullable','integer','exists:conversations,id'],
            'message' => ['required','string'],
            'channel' => ['nullable','string'],
        ]);

        $resp = $this->chat->handle(
            $validated['conversation_id'] ?? null,
            $validated['message'],
            $validated['channel'] ?? 'web',
        );

        return response()->json($resp);
    }
}

