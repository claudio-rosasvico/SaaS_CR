<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LlmGateway
{
    public function generate(array $messages, array $opts = []): string
    {
        $provider = env('LLM_PROVIDER', 'ollama');

        switch ($provider) {
            case 'openai':
                return $this->openai($messages, $opts);
            case 'gemini':
                return $this->gemini($messages, $opts);
            case 'ollama':
                return $this->ollama($messages, $opts);
            default:
                throw new \RuntimeException("LLM_PROVIDER no soportado: $provider");
        }
    }

    /* ---------- OpenAI ---------- */
    protected function openai(array $messages, array $opts): string
    {
        $apiKey = env('OPENAI_API_KEY');
        if (!$apiKey) throw new \RuntimeException('OPENAI_API_KEY vacío');

        $payload = [
            'model' => $opts['model'] ?? env('OPENAI_MODEL', 'gpt-4o-mini'),
            'messages' => $messages,
            'temperature' => $opts['temperature'] ?? env('LLM_TEMPERATURE', 0.2),
            'max_tokens' => $opts['max_tokens'] ?? env('LLM_MAX_TOKENS', 500),
        ];

        $res = Http::timeout(30)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$res->ok()) throw new \RuntimeException('OpenAI error: '.$res->body());

        return $res->json('choices.0.message.content') ?? '';
    }

    /* ---------- Gemini ---------- */
    protected function gemini(array $messages, array $opts): string
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) throw new \RuntimeException('GEMINI_API_KEY vacío');

        // Compactar mensajes a un único prompt (simple y suficiente para nuestro flujo)
        $prompt = collect($messages)->map(function($m){
            return strtoupper($m['role']).": ".$m['content'];
        })->implode("\n\n");

        $model = $opts['model'] ?? env('GEMINI_MODEL', 'gemini-2.0-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => (float)($opts['temperature'] ?? env('LLM_TEMPERATURE', 0.2)),
                'maxOutputTokens' => (int)($opts['max_tokens'] ?? env('LLM_MAX_TOKENS', 500)),
            ],
        ];

        $res = Http::timeout(30)->post($url, $payload);
        if (!$res->ok()) throw new \RuntimeException('Gemini error: '.$res->body());

        return $res->json('candidates.0.content.parts.0.text') ?? '';
    }

    /* ---------- Ollama ---------- */
    protected function ollama(array $messages, array $opts): string
    {
        $base  = rtrim(env('OLLAMA_BASE', 'http://ollama:11434'), '/');
        $model = $opts['model'] ?? env('OLLAMA_MODEL', 'llama3.1');

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => false,
            'options' => [
                'temperature' => (float)($opts['temperature'] ?? env('LLM_TEMPERATURE', 0.2)),
            ],
        ];

        $res = Http::timeout(60)->post($base.'/api/chat', $payload);
        if (!$res->ok()) throw new \RuntimeException('Ollama error: '.$res->body());

        $data = $res->json();
        // Formato nuevo: message.content; form. viejo: response
        return $data['message']['content'] ?? ($data['response'] ?? '');
    }
}
