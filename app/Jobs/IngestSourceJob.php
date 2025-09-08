<?php

namespace App\Jobs;

use App\Models\Source;
use App\Models\KnowledgeChunk;
use App\Jobs\EmbedChunkJob;
use App\Services\PdfUrlExtractor;
use App\Services\Chunker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

class IngestSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $sourceId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $sourceId)
    {
        $this->sourceId = $sourceId;
        $this->onQueue('ingest'); // opcional
    }

    /**
     * Ejecuta la ingesta: extrae texto (PDF/URL/Texto), chunking y encola embeddings.
     */
    public function handle(PdfUrlExtractor $pdf, Chunker $chunker): void
    {
        $source = Source::findOrFail($this->sourceId);
        $source->update(['status' => 'processing', 'error' => null]);

        try {
            $text = '';
            $meta = ['title' => $source->title];

            switch ($source->type) {
                case 'text':
                    $text = (string) $source->text_content;
                    $meta['from'] = 'text';
                    break;

                case 'pdf':
                    // nuestro extractor acepta ruta relativa (disk public) o absoluta
                    $res  = $pdf->extract($source->storage_path);
                    $text = $res['text'];
                    $meta = array_merge($meta, $res['meta'] ?? [], ['from' => 'pdf']);
                    break;

                case 'url':
                    // Detectar PDF por extensión o Content-Type
                    $looksPdf = preg_match('/\.pdf($|\?)/i', (string) $source->url);
                    $ctype = null;
                    try {
                        $head = Http::timeout(8)->head($source->url);
                        if ($head->ok()) $ctype = strtolower($head->header('Content-Type') ?? '');
                    } catch (Throwable $e) { /* ignoramos */
                    }

                    if ($looksPdf || ($ctype && str_contains($ctype, 'pdf'))) {
                        $res  = $pdf->extract($source->url);
                        $text = $res['text'];
                        $meta = array_merge($meta, $res['meta'] ?? [], ['from' => 'url-pdf']);
                    } else {
                        $html = Http::timeout(20)->get($source->url)->body();
                        $text = $this->htmlToText($html);
                        $meta['from'] = 'url-html';
                    }
                    break;

                default:
                    // tipos desconocidos
                    $text = '';
            }

            if (trim($text) === '') {
                $source->update(['status' => 'error', 'error' => 'Sin texto extraído']);
                return;
            }

            // Chunking
            $pieces = $chunker->make($text, 900, 120);

            $ids = [];
            foreach ($pieces as $i => $c) {
                $kc = \App\Models\KnowledgeChunk::create([
                    'organization_id' => $source->organization_id,
                    'source_id'       => $source->id,
                    'position'        => $i + 1,
                    'content'         => $c,
                    'metadata'        => $meta,
                ]);
                $ids[] = $kc->id;
            }

            // Encolar embeddings
            foreach ($ids as $id) {
                EmbedChunkJob::dispatch($id)->onQueue('embeddings');
            }
            $source->update([
                'status'       => 'embedding',
                'chunks_count' => count($ids),
                'embedded_count' => 0,
            ]);

            // Actualizar estado (y chunks_count si existe la columna)
            $update = ['status' => 'ready'];
            if (Schema::hasColumn('sources', 'chunks_count')) {
                $update['chunks_count'] = count($ids);
            }
            $source->update(['status'=>'error','error'=>mb_strimwidth($e->getMessage(),0,240,'…')]);
        } catch (Throwable $e) {
            \Log::error('IngestSourceJob failed', [
                'source_id' => $this->sourceId,
                'error'     => $e->getMessage(),
            ]);

            $source->update([
                'status' => 'error',
                'error'  => mb_strimwidth($e->getMessage(), 0, 240, '…'),
            ]);
        }
    }

    /**
     * Limpieza simple de HTML a texto plano.
     */
    protected function htmlToText(string $html): string
    {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', ' ', $html);
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $html);
        $text = strip_tags($html);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }
}
