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
        // 1) Obtener/crear conversación y FIJAR bot default del canal
        if ($conversationId) {
            $conversation = Conversation::findOrFail($conversationId);
            $channelUsed  = $conversation->channel ?: $channel;
            $defaultBot   = ensure_default_bot($channelUsed);

            if ($conversation->bot_id !== $defaultBot->id) {
                $conversation->bot_id = $defaultBot->id;
                $conversation->save();
            }
            $bot = $defaultBot;
        } else {
            $channelUsed = $channel;
            $bot         = ensure_default_bot($channelUsed);

            $conversation = Conversation::create([
                'channel'         => $channelUsed,
                'started_at'      => now(),
                'organization_id' => current_org_id(),
                'bot_id'          => $bot->id,
            ]);
        }

        // (debug opcional)
        \Log::info('CHAT: usando bot', [
            'conv'     => $conversation->id,
            'org'      => $conversation->organization_id,
            'channel'  => $channelUsed,
            'bot_id'   => $bot->id,
            'bot_name' => $bot->name ?? null,
        ]);

        // 2) Mensaje del usuario
        $userMsg = Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $userText,
            'organization_id' => $conversation->organization_id,
        ]);

        // 3) Métricas
        $__t0 = microtime(true);

        // 4) Retrieval
        $hits    = $this->retrieval->search($userText, 6);
        $context = $this->retrieval->buildContext($hits, 1800);

        // 5) Perfil del bot y construcción del system
        $cfg       = $bot->config ?? [];
        $persona   = trim((string)($cfg['system_prompt'] ?? ''));
        $temp      = (float) ($cfg['temperature']  ?? env('LLM_TEMPERATURE', 0.2));
        $maxTokens = (int)   ($cfg['max_tokens']   ?? env('LLM_MAX_TOKENS', 500));
        $lang      = (string)($cfg['language']     ?? 'es');
        $citations = (bool)  ($cfg['citations']    ?? false);
        $cfg          = $bot->config ?? [];
        $presentation = $cfg['presentation']   ?? [];
        $fallbackCopy = $cfg['fallback_copy']  ?? [];

        $rules = "- Usá SOLO el CONTEXTO proporcionado.\n- Si la información no está, decilo y ofrecé alternativas.\n- Respondé en {$lang} con tono cercano y propositivo.";
        if ($citations) {
            $rules .= "\n- Cuando corresponda, nombrá la localidad/organismo como referencia (sin URLs).";
        }

        $system = $persona !== ''
            ? ($persona . "\n\nReglas:\n" . $rules)
            : ("Asistente útil.\n\nReglas:\n" . $rules);

        // (debug opcional)
        \Log::info('CHAT: params al LLM', [
            'bot_id'  => $bot->id,
            'channel' => $channelUsed,
            'temp'    => $temp,
            'maxTok'  => $maxTokens,
            'sys_head' => mb_substr($system, 0, 160),
        ]);

        // 6) LLM
        try {
            $reply = $this->answerWithLlm($userText, $context, $system, $temp, $maxTokens);

            $usedProvider = env('LLM_PROVIDER', 'ollama');
            switch ($usedProvider) {
                case 'openai':
                    $usedModel = env('OPENAI_MODEL');
                    break;
                case 'gemini':
                    $usedModel = env('GEMINI_MODEL');
                    break;
                default:
                    $usedModel = env('OLLAMA_MODEL');
                    break;
            }
            $usedModel = $usedModel ?: 'unknown';
        } catch (\Throwable $e) {
            \Log::error('LLM fallo', ['error' => $e->getMessage()]);
            $reply        = $this->fallbackFromChunks($hits, $userText, $presentation, $fallbackCopy);
            $usedProvider = 'fallback';
            $usedModel    = 'fragments';
        }

        // 7) Mensaje del asistente
        $assistantMsg = Message::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $reply,
            'meta'            => ['citations' => $this->titlesOnly($hits)],
            'organization_id' => $conversation->organization_id,
        ]);

        // 8) Analytics
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
        // Clamps seguros
        $temp = max(0.0, min(1.0, (float)$temp));
        $maxTokens = max(50, min(4000, (int)$maxTokens));

        if (trim($context) === '') {
            return "No tengo esa info en las fuentes. ¿Querés que te sugiera algunas opciones en Entre Ríos según tu estilo de viaje?";
        }

        $messages = $this->buildMessages($system, $question, $context);

        $text = $this->llm->generate($messages, [
            'temperature' => $temp,
            'max_tokens'  => $maxTokens,
        ]);

        return trim((string)$text) !== '' ? (string)$text : "No puedo responder con la información disponible.";
    }

    protected function fallbackFromChunks(
        array $hits,
        string $q,
        ?array $presentation = null,
        ?array $copy = null
    ): string {
        // Defaults neutros
        $pres = array_replace_recursive([
            'hide_urls'       => true,
            'hide_file_names' => true,
            'aliases'         => ['domains' => [], 'files' => []],
            'max_items'       => 3,
        ], $presentation ?? []);

        // Textos de UI configurables por bot
        $cx = array_replace([
            'empty'   => 'No encontré suficiente información en tus fuentes sobre eso.',
            'intro'   => 'Puedo proponerte opciones relacionadas',
            'joiner'  => ', ',
            'closure' => '¿Querés que detalle alguna?',
        ], $copy ?? []);

        $presenter = app(\App\Services\SourcePresenter::class);

        // Tomar nombres "bonitos" de los hits según política
        $names = [];
        foreach ($hits as $h) {
            $nice = $presenter->present($h, $pres);
            if ($nice) $names[] = $nice;
            if (count($names) >= (int)$pres['max_items']) break;
        }
        $names = array_values(array_unique($names));

        if (empty($names)) {
            return $cx['empty'];
        }

        return $cx['intro'] . ': ' . implode($cx['joiner'], $names) . '. ' . $cx['closure'];
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

    private function buildMessages(string $system, string $question, string $context): array
    {
        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => "Pregunta: {$question}\n\nCONTEXTO:\n{$context}\n\nFin del contexto."],
        ];
    }
}
