<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function embedText(string $text): array
    {
        $prov = env('EMBED_PROVIDER', 'ollama');
        switch ($prov) {
            case 'openai':
                return $this->openai($text);
            case 'gemini':
                return $this->gemini($text);
            default:
                return $this->ollama($text);
        }
    }

    /* ---- OLLAMA ---- */
    protected function ollama(string $text): array
    {
        $base  = rtrim(env('EMBED_OLLAMA_BASE', 'http://ollama:11434'), '/');
        $model = env('EMBED_OLLAMA_MODEL', 'nomic-embed-text');

        $res = Http::timeout(60)->post($base.'/api/embeddings', [
            'model'  => $model,
            'prompt' => $text,
        ]);
        if (!$res->ok()) throw new \RuntimeException('Ollama embeddings error: '.$res->body());

        $vec = $res->json('embedding') ?? [];
        return array_map('floatval', $vec);
    }

    /* ---- OpenAI ---- */
    protected function openai(string $text): array
    {
        $api = env('OPENAI_API_KEY'); if (!$api) throw new \RuntimeException('OPENAI_API_KEY vacío');
        $model = 'text-embedding-3-small';

        $res = Http::timeout(30)->withToken($api)->post('https://api.openai.com/v1/embeddings', [
            'model' => $model,
            'input' => $text,
        ]);
        if (!$res->ok()) throw new \RuntimeException('OpenAI embeddings error: '.$res->body());

        return array_map('floatval', $res->json('data.0.embedding') ?? []);
    }

    /* ---- Gemini ---- */
    protected function gemini(string $text): array
    {
        $key = env('GEMINI_API_KEY'); if (!$key) throw new \RuntimeException('GEMINI_API_KEY vacío');
        $model = 'text-embedding-004';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent?key={$key}";

        $res = Http::timeout(30)->post($url, [
            'content' => ['parts' => [['text' => $text]]],
        ]);
        if (!$res->ok()) throw new \RuntimeException('Gemini embeddings error: '.$res->body());

        return array_map('floatval', $res->json('embedding.values') ?? []);
    }
}
