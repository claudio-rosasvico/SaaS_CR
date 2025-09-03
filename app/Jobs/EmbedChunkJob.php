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
        $chunk = KnowledgeChunk::find($this->chunkId);
        if (!$chunk) return;

        // saltar si ya tiene vector
        if (!empty($chunk->embedding)) return;

        $vec = $emb->embedText($chunk->content);
        $chunk->embedding = $vec;
        $chunk->save();
    }
}
