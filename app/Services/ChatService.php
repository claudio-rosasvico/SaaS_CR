<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;

class ChatService
{
    private RetrievalService $retrieval;
    private LlmGateway $llm;

    public function __construct(RetrievalService $retrieval, LlmGateway $llm)
    {
        $this->retrieval = $retrieval;
        $this->llm = $llm;
    }

    public function handle(?int $conversationId, string $userText, string $channel = 'web'): array
    {
        $conversation = $conversationId
            ? Conversation::findOrFail($conversationId)
            : Conversation::create(['channel' => $channel, 'started_at' => now(), 'organization_id' => current_org_id()]);

        $userMsg = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userText,
            'organization_id' => $conversation->organization_id
        ]);

        $hits = $this->retrieval->search($userText, 6);
        $context = $this->retrieval->buildContext($hits, 1800);

        try {
            $reply = $this->answerWithLlm($userText, $context);
        } catch (\Throwable $e) {
            $reply = $this->fallbackFromChunks($hits, $userText);
        }

        $assistantMsg = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $reply,
            'meta' => ['citations' => $this->titlesOnly($hits)],
            'organization_id' => $conversation->organization_id
        ]);

        return [
            'conversation_id' => $conversation->id,
            'messages' => [
                ['id' => $userMsg->id, 'role' => $userMsg->role, 'content' => $userMsg->content],
                ['id' => $assistantMsg->id, 'role' => $assistantMsg->role, 'content' => $assistantMsg->content],
            ],
            'citations' => $assistantMsg->meta['citations'] ?? [],
        ];
    }

    protected function answerWithLlm(string $question, string $context): string
    {
        if (trim($context) === '') {
            return "No encontré información en tus fuentes para “{$question}”. Probá subir documentos o ajustar la pregunta.";
        }

        $messages = [
            ['role' => 'system', 'content' => "Eres un asistente que responde SOLO con el CONTEXTO dado. Si algo no está en el contexto, di que no lo sabes. Sé claro y conciso."],
            ['role' => 'user', 'content' => "Pregunta: {$question}\n\nCONTEXTO:\n{$context}\n\nFin del contexto."],
        ];

        $text = $this->llm->generate($messages, [
            'temperature' => (float)env('LLM_TEMPERATURE', 0.2),
            'max_tokens'  => (int)env('LLM_MAX_TOKENS', 500),
        ]);

        return trim($text) !== '' ? $text : "No puedo responder con la información disponible.";
    }

    protected function fallbackFromChunks(array $hits, string $q): string
    {
        if (empty($hits)) {
            return "No encontré información en tus fuentes para “{$q}”.";
        }
        $lines = ["Según tus fuentes encontré:"];
        foreach (array_slice($hits, 0, 3) as $h) {
            $title = $h['metadata']['title'] ?? ($h['metadata']['file'] ?? 'Fuente');
            $txt   = trim(mb_strimwidth(preg_replace('/\s+/', ' ', $h['content']), 0, 240, '…'));
            $lines[] = "• {$title}: {$txt}";
        }
        return implode("\n", $lines);
    }

    protected function titlesOnly(array $hits): array
    {
        $titles = [];
        foreach ($hits as $h) {
            $titles[] = $h['metadata']['title'] ?? ($h['metadata']['file'] ?? 'Fuente');
        }
        $titles = array_values(array_unique($titles));
        return array_map(fn($t) => ['title' => $t], $titles);
    }
}
