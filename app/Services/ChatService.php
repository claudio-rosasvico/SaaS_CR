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
        private LlmGateway $llm,
        private SourcePresenter $presenter
    ) {}

    public function handle(?int $conversationId, string $userText, string $channel = 'web'): array
    {
        // Bot por canal/orga
        $orgId = $conversationId
            ? optional(Conversation::findOrFail($conversationId))->organization_id
            : current_org_id();

        $bot = ensure_default_bot($channel, $orgId);

        // Crear u obtener conversación
        $conversation = $conversationId
            ? Conversation::findOrFail($conversationId)
            : Conversation::create([
                'channel'         => $channel,
                'started_at'      => now(),
                'organization_id' => $orgId,
                'bot_id'          => $bot->id,
            ]);

        // Re-afirmar bot si faltara
        if (!$conversation->bot_id) {
            $conversation->bot_id = $bot->id;
            $conversation->save();
        }

        // Guardar mensaje del usuario
        $userMsg = Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $userText,
            'organization_id' => $conversation->organization_id,
        ]);

        $t0 = microtime(true);

        // Retrieval según preferencia del bot
        $cfg           = (array)($bot->config ?? []);
        $retrievalMode = (string)($cfg['retrieval_mode'] ?? env('RETRIEVAL_MODE', 'semantic'));
        $hits          = $this->retrieval->search($userText, 8, $retrievalMode);
        $context       = $this->retrieval->buildContext($hits, 1800);

        // Parámetros del bot (con clamps)
        $persona   = trim((string)($cfg['system_prompt'] ?? ''));
        $temp      = max(0.0, min(1.0, (float)($cfg['temperature'] ?? env('LLM_TEMPERATURE', 0.2))));
        $maxTokens = (int)($cfg['max_tokens'] ?? env('LLM_MAX_TOKENS', 500));
        $maxTokens = max(50, min(4000, $maxTokens));
        $lang      = (string)($cfg['language'] ?? 'es');
        $citations = (bool)  ($cfg['citations'] ?? false);

        // Reglas del system prompt
        $rules = "- Usa SOLO el CONTEXTO proporcionado.\n- Si la info no está, dilo.\n- Responde en {$lang} con estilo claro y amable.";
        if ($citations) {
            $rules .= "\n- Cuando corresponda, incluye una breve referencia entre corchetes.";
        }
        $system = $persona !== '' ? ($persona . "\n\nReglas:\n" . $rules) : ("Asistente útil.\n\nReglas:\n" . $rules);

        // Si no hay contexto: respuesta suave sin listar “fuentes”
        if (trim($context) === '') {
            $reply = "No encuentro eso en tus fuentes ahora mismo. ¿Querés que busque aproximaciones o preferís cargar nuevo material?";
            $assistantMsg = Message::create([
                'conversation_id' => $conversation->id,
                'role'            => 'assistant',
                'content'         => $reply,
                'meta'            => ['citations' => []],
                'organization_id' => $conversation->organization_id,
            ]);
            return [
                'conversation_id' => $conversation->id,
                'messages'        => [
                    ['id' => $userMsg->id,      'role' => $userMsg->role,      'content' => $userMsg->content],
                    ['id' => $assistantMsg->id, 'role' => $assistantMsg->role, 'content' => $assistantMsg->content],
                ],
                'citations'       => [],
            ];
        }

        // Llamada al LLM
        $messages = $this->buildMessages($system, $userText, $context);

        try {
            $text = $this->llm->generate($messages, [
                'temperature' => $temp,
                'max_tokens'  => $maxTokens,
            ]);
            $reply = trim((string)$text) !== '' ? (string)$text : $this->presenter->fallback($hits, $cfg);

            $provider = env('LLM_PROVIDER', 'ollama');
            $model    = match ($provider) {
                'openai' => env('OPENAI_MODEL'),
                'gemini' => env('GEMINI_MODEL'),
                default  => env('OLLAMA_MODEL'),
            } ?: 'unknown';
        } catch (\Throwable $e) {
            \Log::error('LLM fallo', ['error' => $e->getMessage()]);
            $reply    = $this->presenter->fallback($hits, $cfg);
            $provider = 'fallback';
            $model    = 'fragments';
        }

        // Guardar respuesta (sin citas si están ocultas)
        $assistantMsg = Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $reply,
            'meta'            => ['citations' => $this->presenter->citations($hits, $cfg)],
            'organization_id' => $conversation->organization_id,
        ]);

        // Métricas
        $durMs    = (int) round((microtime(true) - $t0) * 1000);
        $tokIn    = (int) ceil(mb_strlen($context) / 4);
        $tokOut   = (int) ceil(mb_strlen($reply)   / 4);

        try {
            AnalyticsEvent::create([
                'organization_id' => $conversation->organization_id,
                'conversation_id' => $conversation->id,
                'provider'        => $provider,
                'model'           => $model,
                'duration_ms'     => $durMs,
                'tokens_in'       => $tokIn,
                'tokens_out'      => $tokOut,
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

    private function buildMessages(string $system, string $question, string $context): array
    {
        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => "Pregunta: {$question}\n\nCONTEXTO:\n{$context}\n\nFin del contexto."],
        ];
    }
}
