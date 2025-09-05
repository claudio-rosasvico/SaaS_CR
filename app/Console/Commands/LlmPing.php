<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LlmGateway;

class LlmPing extends Command
{
    protected $signature = 'llm:ping {text=Decime una oración corta en español.}';
    protected $description = 'Prueba el LLM configurado y muestra una respuesta breve';

    public function handle(LlmGateway $llm): int
    {
        $text = $this->argument('text');

        $messages = [
            ['role' => 'system', 'content' => 'Responde en español, breve.'],
            ['role' => 'user',   'content' => $text],
        ];

        $out = $llm->generate($messages, [
            'temperature' => (float) env('LLM_TEMPERATURE', 0.2),
            'max_tokens'  => (int)   env('LLM_MAX_TOKENS', 300),
        ]);

        $this->info(trim($out) !== '' ? $out : 'Sin respuesta.');
        return self::SUCCESS;
    }
}
