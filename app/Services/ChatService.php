<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\AnalyticsEvent;
use App\Models\Bot;

class ChatService
{
    public function __construct(
        private RetrievalService $retrieval,
        private LlmGateway $llm
    ) {}

    public function handle(?int $conversationId, string $userText, string $channel = 'web'): array
    {
        $conversation = $conversationId
            ? Conversation::findOrFail($conversationId)
            : Conversation::create([
                'channel'          => $channel,
                'started_at'       => now(),
                'organization_id'  => current_org_id(),
                'bot_id'           => ensure_default_bot()->id,
            ]);

        // Asegurar bot asignado
        $bot = $conversation->bot_id
            ? Bot::find($conversation->bot_id)
            : ensure_default_bot();

        if (!$conversation->bot_id && $bot) {
            $conversation->bot_id = $bot->id;
            $conversation->save();
        }

        $userMsg = Message::create([
            'conversation_id'   => $conversation->id,
            'role'              => 'user',
            'content'           => $userText,
            'organization_id'   => $conversation->organization_id,
        ]);

        // --- Métricas: cronómetro
        $__t0 = microtime(true);

        // Retrieval
        $hits    = $this->retrieval->search($userText, 6);
        $context = $this->retrieval->buildContext($hits, 1800);

        // Persona y parámetros del bot
        $cfg        = $bot?->config ?? [];
        $persona    = trim((string)($cfg['system_prompt'] ?? ''));
        $temp       = (float) ($cfg['temperature']  ?? env('LLM_TEMPERATURE', 0.2));
        $maxTokens  = (int)   ($cfg['max_tokens']   ?? env('LLM_MAX_TOKENS', 500));
        $lang       = (string)($cfg['language']     ?? 'es');
        $citations  = (bool)  ($cfg['citations']    ?? false);

        // Construimos el system con la persona + reglas de uso de contexto
        $rules = "- Usa SOLO el CONTEXTO proporcionado.\n- Si la información no está, dilo.\n- Responde en {$lang} con frases breves.";
        if ($citations) {
            $rules .= "\n- Cuando corresponda, cita la fuente por título entre [corchetes].";
        }
        $system = $persona !== '' ? ($persona."\n\nReglas:\n".$rules) : ("Asistente útil.\n\nReglas:\n".$rules);

        try {
            $reply = $this->answerWithLlm($userText, $context, $system, $temp, $maxTokens);
            $usedProvider = env('LLM_PROVIDER', 'ollama');
            $usedModel    = match ($usedProvider) {
                'openai' => env('OPENAI_MODEL'),
                'gemini' => env('GEMINI_MODEL'),
                default  => env('OLLAMA_MODEL'),
            } ?? 'unknown';
        } catch (\Throwable $e) {
            \Log::error('LLM fallo', ['error' => $e->getMessage()]);
            $reply        = $this->fallbackFromChunks($hits, $userText);
            $usedProvider = 'fallback';
            $usedModel    = 'fragments';
        }

        $assistantMsg = Message::create([
            'conversation_id'   => $conversation->id,
            'role'              => 'assistant',
            'content'           => $reply,
            'meta'              => ['citations' => $this->titlesOnly($hits)],
            'organization_id'   => $conversation->organization_id,
        ]);

        // Métricas
        $durationMs = (int) round((microtime(true) - $__t0) * 1000);
        $tokensIn   = (int) ceil(mb_strlen($context) / 4);
        $tokensOut  = (int) ceil(mb_strlen($reply)   / 4);

        try {
            AnalyticsEvent::create([
                'organization_id' => $conversation->organization_id,
                'conversation_id' => $conversation->id,
                'provider'        => $usedProvider,
                'model'           => $usedModel,
                'duration_ms'     => $durationMs,
                'tokens_in'       => $tokensIn,
                'tokens_out'      => $tokensOut,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('No se pudo guardar AnalyticsEvent', ['error' => $e->getMessage()]);
        }

        return [
            'conversation_id' => $conversation->id,
            'messages' => [
                ['id' => $userMsg->id,      'role' => $userMsg->role,      'content' => $userMsg->content],
                ['id' => $assistantMsg->id, 'role' => $assistantMsg->role, 'content' => $assistantMsg->content],
            ],
            'citations' => $assistantMsg->meta['citations'] ?? [],
        ];
    }

    protected function answerWithLlm(string $question, string $context, string $system, float $temp, int $maxTokens): string
    {
        if (trim($context) === '') {
            return "No encontré información en tus fuentes para “{$question}”. Probá subir documentos o ajustar la pregunta.";
        }

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => "Pregunta: {$question}\n\nCONTEXTO:\n{$context}\n\nFin del contexto."],
        ];

        $text = $this->llm->generate($messages, [
            'temperature' => $temp,
            'max_tokens'  => $maxTokens,
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
