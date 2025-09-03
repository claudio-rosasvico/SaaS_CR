<?php

namespace App\Livewire\Panel;

use App\Models\Source;
use App\Models\KnowledgeChunk;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Http;
use Spatie\PdfToText\Pdf as SpatiePdf;
use Smalot\PdfParser\Parser as SmalotPdfParser;
use App\Jobs\IngestSourceJob;

class SourcesPage extends Component
{
    use WithFileUploads;

    public string $type = 'text'; // text|url|pdf
    public ?string $title = null;
    public ?string $text_content = null;
    public ?string $url = null;

    #[Validate('nullable|file|mimes:pdf|max:10240')]
    public $pdf;

    public function save()
    {
        $this->validate(); // tus validaciones actuales

        $source = new \App\Models\Source();
        $source->organization_id = current_org_id();
        $source->type = $this->type;
        $source->title = $this->title;

        if ($this->type === 'text') {
            $source->text_content = $this->text_content;
        } elseif ($this->type === 'url') {
            $source->url = $this->url;
        } else { // pdf
            $path = $this->pdf->store('sources', 'public');
            $source->storage_path = $path;
        }

        $source->status = 'queued';
        $source->save();

        // 👇 encolamos la ingesta (no bloquea la UI)
        IngestSourceJob::dispatch($source->id);

        session()->flash('ok', 'Fuente encolada para procesamiento.');
        // limpiar inputs si querés
    }

    public function process($id)
    {
        $source = Source::findOrFail($id);
        $this->ingest($source);
        session()->flash('ok', 'Fuente procesada.');
    }

    protected function ingest(Source $source)
    {
        $fullText = '';

        if ($source->type === 'text') {
            $fullText = (string) $source->text_content;
        } elseif ($source->type === 'url' && $source->url) {
            try {
                $html = \Illuminate\Support\Facades\Http::timeout(10)->get($source->url)->body();
                $fullText = $this->stripHtmlToText($html);
            } catch (\Throwable $e) {
                $source->status = 'error';
                $source->meta = ['error' => $e->getMessage()];
                $source->save();
                return;
            }
        } elseif ($source->type === 'pdf' && $source->storage_path) {
            try {
                $absPath = storage_path('app/public/' . $source->storage_path);
                $fullText = $this->extractPdfText($absPath);
            } catch (\Throwable $e) {
                $source->status = 'error';
                $source->meta = ['error' => $e->getMessage()];
                $source->save();
                return;
            }
        }

        // Chunking simple
        $chunks = $this->chunkText($fullText, 900, 120);

        foreach ($chunks as $i => $chunk) {
            \App\Models\KnowledgeChunk::create([
                'source_id' => $source->id,
                'position'  => $i + 1,
                'content'   => $chunk,
                'metadata'  => [
                    'from'  => $source->type,
                    'title' => $source->title,
                ],
                'organization_id' => $source->organization_id,
            ]);
        }

        $source->status = 'ready';
        $source->save();
    }
    protected function extractPdfText(string $absPath): string
    {
        // 1) Intento con Spatie (pdftotext)
        try {
            // Podés pasar opciones si querés conservar layout: ->setOptions(['-layout'])
            return (new SpatiePdf())
                ->setPdf($absPath)
                ->text();
        } catch (\Throwable $e) {
            // seguimos al fallback
        }

        // 2) Fallback Smalot (puro PHP)
        if (class_exists(SmalotPdfParser::class)) {
            $parser = new SmalotPdfParser();
            $pdf = $parser->parseFile($absPath);
            $text = $pdf->getText();
            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        }

        throw new \RuntimeException('No se pudo extraer texto del PDF con ninguna estrategia.');
    }

    protected function stripHtmlToText(string $html): string
    {
        $text = preg_replace('#<script(.*?)>(.*?)</script>#is', ' ', $html);
        $text = preg_replace('#<style(.*?)>(.*?)</style>#is', ' ', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_replace('/\s+/', ' ', $text);
    }

    protected function chunkText(string $text, int $size = 900, int $overlap = 120): array
    {
        $text = trim($text);
        if ($text === '') return [];
        $out = [];
        $start = 0;
        $len = strlen($text);

        while ($start < $len) {
            $end = min($start + $size, $len);
            // evita cortar a mitad de palabra
            if ($end < $len) {
                $nextSpace = strpos($text, ' ', $end);
                if ($nextSpace !== false && $nextSpace - $end < 40) {
                    $end = $nextSpace;
                }
            }
            $out[] = trim(substr($text, $start, $end - $start));
            if ($end >= $len) break;
            $start = max(0, $end - $overlap);
        }

        return $out;
    }

    public function render()
    {
        return view('livewire.panel.sources-page', [
            'sources' => Source::latest()->paginate(10),
        ]);
    }
}
