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

    public function handle(EmbeddingService $emb): void
    {
        $chunk = KnowledgeChunk::findOrFail($this->chunkId);
        $vec = $emb->embed($chunk->content);
        if (!$vec) throw new \RuntimeException('Embedding vacío');

        $chunk->embedding = json_encode($vec);
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
