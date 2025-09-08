<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeChunk;
use App\Services\EmbeddingService;

class RagEmbedMissing extends Command
{
    protected $signature = 'rag:embed-missing {--limit=500}';
    protected $description = 'Genera embeddings para chunks sin vector';

    public function handle(EmbeddingService $emb)
    {
        $limit = (int)$this->option('limit');
        $q = KnowledgeChunk::whereNull('embedding')->limit($limit)->get();
        if ($q->isEmpty()) { $this->info('No hay chunks pendientes.'); return 0; }

        $dim = (int) env('EMBED_DIM', 768);
        $done = 0;

        foreach ($q as $chunk) {
            try {
                $vec = $emb->embed($chunk->content);
                // chequear dimensión opcional
                if ($dim > 0 && count($vec) !== $dim) {
                    $this->warn("Dimensión inesperada (".count($vec).") en chunk {$chunk->id}");
                }
                $chunk->embedding = $vec;
                $chunk->save();
                $done++;
            } catch (\Throwable $e) {
                $this->error("Error en chunk {$chunk->id}: ".$e->getMessage());
            }
        }

        $this->info("Listo: {$done} chunks vectorizados.");
        return 0;
    }
}
