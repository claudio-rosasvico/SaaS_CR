<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\AnalyticsEvent;
use App\Models\Bot;
use App\Services\RetrievalService;
use App\Services\LlmGateway;
use Illuminate\Http\Request;

class ChatStreamController extends Controller
{
    public function __invoke(Request $req, RetrievalService $retrieval, LlmGateway $llm)
    {
        $userText = (string) $req->input('q', '');
        $convId   = $req->input('conversation_id');
        $channel  = (string) $req->input('channel', 'web');

        // Crear/obtener conversación con bot por canal
        $conversation = $convId
            ? Conversation::findOrFail($convId)
            : Conversation::create([
                'channel'          => $channel,
                'started_at'       => now(),
                'organization_id'  => current_org_id(),
                'bot_id'           => ensure_default_bot($channel)->id, // 👈 aquí
            ]);

        // Si la conversación ya existía pero no tiene bot/canal, completamos
        $channel = $conversation->channel ?: $channel;

        $bot = $conversation->bot_id
            ? Bot::find($conversation->bot_id)
            : ensure_default_bot($channel);

        if (!$conversation->bot_id && $bot) {
            $conversation->bot_id = $bot->id;
            $conversation->save();
        }


        // Guardar mensaje del usuario
        $userMsg = Message::create([
            'conversation_id' => $conversation->id,
            'organization_id' => $conversation->organization_id,
            'role'            => 'user',
            'content'         => $userText,
        ]);

        // Retrieval
        $hits    = $retrieval->search($userText, 6);
        $context = $retrieval->buildContext($hits, 1800);

        // Config del bot (personalidad/parámetros)
        $cfg       = $bot?->config ?? [];
        $persona   = trim((string)($cfg['system_prompt'] ?? ''));
        $temp      = (float) ($cfg['temperature'] ?? env('LLM_TEMPERATURE', 0.2));
        $maxTokens = (int)   ($cfg['max_tokens']  ?? env('LLM_MAX_TOKENS', 500));
        $lang      = (string)($cfg['language']    ?? 'es');
        $citations = (bool)  ($cfg['citations']   ?? false);

        $rules = "- Usa SOLO el CONTEXTO proporcionado.\n- Si la información no está, dilo.\n- Responde en {$lang} con frases breves.";
        if ($citations) {
            $rules .= "\n- Cuando corresponda, cita la fuente por título entre [corchetes].";
        }
        $system = $persona !== '' ? ($persona . "\n\nReglas:\n" . $rules)
            : ("Asistente útil.\n\nReglas:\n" . $rules);

        // Si no hay contexto, devolvemos respuesta corta sin stream (y persistimos)
        if (trim($context) === '') {
            $reply = "No encontré información en tus fuentes para “{$userText}”. Probá subir documentos o ajustar la pregunta.";
            Message::create([
                'conversation_id' => $conversation->id,
                'organization_id' => $conversation->organization_id,
                'role'            => 'assistant',
                'content'         => $reply,
                'meta'            => ['citations' => $this->titlesOnly($hits)],
            ]);
            return response($reply, 200, [
                'Content-Type'       => 'text/plain; charset=utf-8',
                'X-Accel-Buffering'  => 'no',
                'Cache-Control'      => 'no-cache',
            ]);
        }

        // Mensajes para el LLM
        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => "Pregunta: {$userText}\n\nCONTEXTO:\n{$context}\n\nFin del contexto."],
        ];

        $provider = env('LLM_PROVIDER', 'ollama');
        $model    = $provider === 'ollama'
            ? env('OLLAMA_MODEL')
            : (env('OPENAI_MODEL') ?? env('GEMINI_MODEL'));

        return response()->stream(function () use ($llm, $messages, $conversation, $hits, $provider, $model, $context, $temp, $maxTokens) {
            $t0    = microtime(true);
            $reply = '';
            $flush = function () {
                @ob_flush();
                @flush();
            };

            try {
                $llm->stream($messages, [
                    'temperature' => $temp,
                    'num_ctx'     => 1024,       // opcional (útil en notebooks)
                    'max_tokens'  => $maxTokens, // LlmGateway debe mapear a num_predict para Ollama
                ], function ($delta) use (&$reply, $flush) {
                    $reply .= $delta;
                    echo $delta;
                    $flush();
                });
            } catch (\Throwable $e) {
                \Log::error('Stream LLM error', ['error' => $e->getMessage()]);
                $fallback = $this->fallbackFromChunks($hits, $messages[1]['content'] ?? '');
                $reply = $fallback;
                echo $fallback;
                $flush();
            }

            // Guardar mensaje del asistente + métricas
            Message::create([
                'conversation_id' => $conversation->id,
                'organization_id' => $conversation->organization_id,
                'role'            => 'assistant',
                'content'         => trim($reply) !== '' ? $reply : "No puedo responder con la información disponible.",
                'meta'            => ['citations' => $this->titlesOnly($hits)],
            ]);

            $dur = (int) round((microtime(true) - $t0) * 1000);
            AnalyticsEvent::create([
                'organization_id' => $conversation->organization_id,
                'conversation_id' => $conversation->id,
                'provider'        => $provider,
                'model'           => $model ?? 'unknown',
                'duration_ms'     => $dur,
                'tokens_in'       => (int) ceil(mb_strlen($context) / 4),
                'tokens_out'      => (int) ceil(mb_strlen($reply)   / 4),
            ]);
        }, 200, [
            'Content-Type'      => 'text/plain; charset=utf-8',
            'X-Accel-Buffering' => 'no',
            'Cache-Control'     => 'no-cache',
        ]);
    }

    protected function fallbackFromChunks(array $hits, string $q): string
    {
        if (empty($hits)) {
            return "No encontré información en tus fuentes para esa consulta.";
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
        return array_values(array_unique($titles));
    }
}
