<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function embed(string $text): array
    {
        $provider = env('EMBED_PROVIDER', 'ollama');

        if ($provider === 'ollama') {
            $base  = rtrim(env('EMBED_OLLAMA_BASE', env('OLLAMA_BASE', 'http://host.docker.internal:11434')), '/');
            $model = env('EMBED_OLLAMA_MODEL', env('OLLAMA_EMBED_MODEL', 'nomic-embed-text'));

            $res = Http::timeout(60)->post($base . '/api/embeddings', [
                'model'  => $model,
                'prompt' => $text,
            ]);

            if (!$res->ok()) {
                throw new \RuntimeException('Ollama embeddings error: ' . $res->body());
            }

            $vec = $res->json('embedding') ?? null;
            if (!is_array($vec)) {
                throw new \RuntimeException('Ollama embeddings no devolvió "embedding".');
            }
            return $vec;
        }

        throw new \InvalidArgumentException('EMBED_PROVIDER no soportado: ' . $provider);
    }

    public function embedText(string $t): array
    {  
        return $this->embed($t);
    }

    /* ---- OpenAI ---- */
    protected function openai(string $text): array
    {
        $api = env('OPENAI_API_KEY');
        if (!$api) throw new \RuntimeException('OPENAI_API_KEY vacío');
        $model = 'text-embedding-3-small';

        $res = Http::timeout(30)->withToken($api)->post('https://api.openai.com/v1/embeddings', [
            'model' => $model,
            'input' => $text,
        ]);
        if (!$res->ok()) throw new \RuntimeException('OpenAI embeddings error: ' . $res->body());

        return array_map('floatval', $res->json('data.0.embedding') ?? []);
    }

    /* ---- Gemini ---- */
    protected function gemini(string $text): array
    {
        $key = env('GEMINI_API_KEY');
        if (!$key) throw new \RuntimeException('GEMINI_API_KEY vacío');
        $model = 'text-embedding-004';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent?key={$key}";

        $res = Http::timeout(30)->post($url, [
            'content' => ['parts' => [['text' => $text]]],
        ]);
        if (!$res->ok()) throw new \RuntimeException('Gemini embeddings error: ' . $res->body());

        return array_map('floatval', $res->json('embedding.values') ?? []);
    }
}
