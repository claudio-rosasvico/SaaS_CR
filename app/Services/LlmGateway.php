<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LlmGateway
{
    /**
     * Generación no-stream.
     * @param array $messages  Conversación estilo OpenAI: [['role'=>'system'|'user'|'assistant','content'=>'...'], ...]
     * @param array $opts      ['temperature'=>float, 'max_tokens'=>int, ...]
     */
    public function generate(array $messages, array $opts = []): string
    {
        $provider = env('LLM_PROVIDER', 'ollama');

        try {
            return match ($provider) {
                'openai' => $this->openaiGenerate($messages, $opts),
                'gemini' => $this->geminiGenerate($messages, $opts), // stub seguro
                default  => $this->ollamaGenerate($messages, $opts), // ollama
            };
        } catch (\Throwable $e) {
            \Log::error('LLM generate failed', ['provider' => $provider, 'error' => $e->getMessage()]);
            // SIEMPRE devolvemos string
            return '';
        }
    }

    /**
     * Streaming: si tu controlador usa stream, acá podés emitir por callback.
     * Si el provider no soporta stream, hacemos “pseudo-stream”: llamamos generate y enviamos de una.
     */
    public function stream(array $messages, array $opts, callable $onDelta): void
    {
        $provider = env('LLM_PROVIDER', 'ollama');

        // Por ahora: implementamos stream real para Ollama,
        // y para OpenAI/Gemini emitimos todo de una (para no romper controladores existentes).
        try {
            if ($provider === 'ollama') {
                $this->ollamaStream($messages, $opts, $onDelta);
                return;
            }

            $txt = $this->generate($messages, $opts);
            if ($txt !== '') {
                $onDelta($txt);
            }
        } catch (\Throwable $e) {
            \Log::error('LLM stream failed', ['provider' => $provider, 'error' => $e->getMessage()]);
        }
    }

    // ============ OPENAI ============
    private function openaiGenerate(array $messages, array $opts): string
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY vacío');
        }

        $model = env('OPENAI_MODEL', 'gpt-4o-mini');
        $temperature = (float)($opts['temperature'] ?? env('LLM_TEMPERATURE', 0.2));
        $maxTokens   = (int)  ($opts['max_tokens']  ?? (int)env('LLM_MAX_TOKENS', 500));

        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => $temperature,
            'max_tokens'  => $maxTokens,
        ];

        $resp = Http::withToken($apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$resp->successful()) {
            throw new \RuntimeException('OpenAI: '.$resp->status().' '.$resp->body());
        }

        $data = $resp->json();
        $text = $data['choices'][0]['message']['content'] ?? '';
        return (string)$text;
    }

    // ============ OLLAMA ============
    private function ollamaGenerate(array $messages, array $opts): string
    {
        $base = rtrim(env('OLLAMA_BASE', 'http://host.docker.internal:11434'), '/');
        $model = env('OLLAMA_MODEL', 'llama3.1:8b-instruct');

        // Opciones de Ollama
        $temperature = (float)($opts['temperature'] ?? env('LLM_TEMPERATURE', 0.2));
        $maxTokens   = (int)  ($opts['max_tokens']  ?? (int)env('LLM_MAX_TOKENS', 500));

        $payload = [
            'model'    => $model,
            'messages' => $messages,
            'stream'   => false, // importante: respuesta no streamed
            'options'  => [
                'temperature' => $temperature,
                'num_predict' => $maxTokens,
                // 'num_ctx'   => 2048, // si querés
            ],
        ];

        $resp = Http::timeout(120)->post($base.'/api/chat', $payload);
        if (!$resp->successful()) {
            throw new \RuntimeException('Ollama: '.$resp->status().' '.$resp->body());
        }

        $data = $resp->json();

        // Ollama (stream=false) retorna algo tipo:
        // {
        //   "model":"...","created_at":"...","message":{"role":"assistant","content":"..."},
        //   "done":true, ...
        // }
        $text = $data['message']['content'] ?? ($data['response'] ?? '');
        return (string)$text;
    }

    private function ollamaStream(array $messages, array $opts, callable $onDelta): void
    {
        $base = rtrim(env('OLLAMA_BASE', 'http://host.docker.internal:11434'), '/');
        $model = env('OLLAMA_MODEL', 'llama3.1:8b-instruct');

        $temperature = (float)($opts['temperature'] ?? env('LLM_TEMPERATURE', 0.2));
        $maxTokens   = (int)  ($opts['max_tokens']  ?? (int)env('LLM_MAX_TOKENS', 500));

        $payload = [
            'model'    => $model,
            'messages' => $messages,
            'stream'   => true,
            'options'  => [
                'temperature' => $temperature,
                'num_predict' => $maxTokens,
            ],
        ];

        // Stream chunked (cada línea JSON):
        Http::timeout(0)->withOptions(['stream' => true])->post($base.'/api/chat', $payload)
            ->throw()
            ->getBody()
            ->each(function ($chunk) use ($onDelta) {
                $line = trim((string)$chunk);
                if ($line === '') return;
                // cada línea es un JSON con 'message'=>['content'=>'delta parcial'] o 'done'=>true
                $data = json_decode($line, true);
                if (!is_array($data)) return;
                $delta = $data['message']['content'] ?? '';
                if ($delta !== '') {
                    $onDelta($delta);
                }
            });
    }

    // ============ GEMINI (stub seguro) ============
    private function geminiGenerate(array $messages, array $opts): string
    {
        // Dejamos como stub para ahora. Siempre devolver string.
        // Si luego querés implementarlo, similar a OpenAI (endpoint y formato de mensajes distinto).
        return '';
    }
}