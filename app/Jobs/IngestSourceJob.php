<?php

namespace App\Jobs;

use App\Models\Source;
use App\Models\KnowledgeChunk;
use App\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IngestSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $sourceId) {}

    public function handle(): void
    {
        $source = Source::findOrFail($this->sourceId);
        $source->update(['status' => 'processing']);

        // según el tipo, extraemos texto
        $text = '';
        if ($source->type === 'text') {
            $text = (string) $source->text_content;
        } elseif ($source->type === 'url') {
            // usa tu método/servicio actual para scrapear la URL
            $text = app('App\Services\PdfUrlExtractor')->extractFromUrl($source->url);
        } elseif ($source->type === 'pdf') {
            $abs = storage_path('app/public/'.$source->storage_path);
            $text = app('App\Services\PdfUrlExtractor')->extractFromPdf($abs);
        }

        // chunkear (usa tu chunker actual)
        $chunks = app('App\Services\Chunker')->make($text, 900, 120);

        $chunkIds = [];
        foreach ($chunks as $i => $c) {
            $kc = KnowledgeChunk::create([
                'organization_id' => $source->organization_id,
                'source_id'       => $source->id,
                'position'        => $i + 1,
                'content'         => $c,
                'metadata'        => ['title' => $source->title, 'from' => $source->type],
            ]);
            $chunkIds[] = $kc->id;
        }

        // Encolar embeddings (cola dedicada)
        foreach ($chunkIds as $id) {
            EmbedChunkJob::dispatch($id)->onQueue('embeddings');
        }

        $source->update(['status' => 'ready']);
    }
}
