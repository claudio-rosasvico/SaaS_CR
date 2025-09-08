<?php

namespace App\Jobs;

use App\Models\KnowledgeChunk;
use App\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EmbedChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $chunkId) {}

    public function handle(\App\Services\EmbeddingService $emb): void
    {
        $chunk = \App\Models\KnowledgeChunk::find($this->chunkId);
        if (!$chunk) return;
        if (!empty($chunk->embedding)) return;

        $vec = $emb->embed($chunk->content);     // 🔌 llama a Ollama embeddings
        $chunk->embedding   = $vec;              // guarda JSON
        $chunk->embedded_at = now();
        $chunk->save();

        // (Opcional) si llevás contadores para cerrar en ready:
        $src = \App\Models\Source::find($chunk->source_id);
        if (
            $src && \Illuminate\Support\Facades\Schema::hasColumn('sources', 'embedded_count') &&
            \Illuminate\Support\Facades\Schema::hasColumn('sources', 'chunks_count')
        ) {
            $src->increment('embedded_count');
            if ((int)$src->embedded_count >= (int)$src->chunks_count) {
                $src->update(['status' => 'ready']);
            } else if ($src->status !== 'embedding') {
                $src->update(['status' => 'embedding']);
            }
        }
    }
}
