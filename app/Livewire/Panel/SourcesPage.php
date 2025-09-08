<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Source;
use App\Jobs\IngestSourceJob;
use Illuminate\Support\Str;

class SourcesPage extends Component
{
    use WithFileUploads, WithPagination;

    // si usás Bootstrap:
    protected $paginationTheme = 'bootstrap';

    public string $type = 'text'; // text|url|pdf
    public ?string $title = null;
    public ?string $text_content = null;
    public ?string $url = null;
    public $pdf = null;

    public function save(): void
    {
        $this->validate(match ($this->type) {
            'text' => [
                'title'        => 'nullable|string|max:160',
                'text_content' => 'required|string|min:10',
            ],
            'url'  => [
                'title' => 'nullable|string|max:160',
                'url'   => 'required|url|max:2048',
            ],
            'pdf'  => [
                'title' => 'nullable|string|max:160',
                'pdf'   => 'required|file|mimes:pdf|max:20480',
            ],
        });

        $s = new Source();
        $s->type   = $this->type;
        $s->status = 'queued';
        $s->title  = trim((string)$this->title) !== ''
            ? $this->title
            : match ($this->type) {
                'pdf'  => $this->pdf?->getClientOriginalName() ?? 'Documento PDF',
                'url'  => Str::limit((string)$this->url, 80),
                default => 'Texto pegado',
            };

        if ($this->type === 'text')  $s->text_content = $this->text_content;
        if ($this->type === 'url')   $s->url          = $this->url;
        if ($this->type === 'pdf')   $s->storage_path = $this->pdf->store('tenants/'.current_org_id().'/sources', 'public');

        $s->save();

        // encolamos el procesamiento
        IngestSourceJob::dispatch($s->id);

        session()->flash('ok', 'Fuente encolada para procesar.');
        $this->reset(['title','text_content','url','pdf']);
        $this->type = 'text';
        $this->resetPage(); // vuelve a la página 1 de la paginación
    }

    public function process(int $id): void
    {
        IngestSourceJob::dispatch($id);
        session()->flash('ok', 'Procesamiento re-enviado a la cola.');
    }

    public function render()
    {
        // si querés el conteo de chunks, podés sumar ->withCount('chunks')
        $sources = Source::orderByDesc('id')->paginate(10);

        return view('livewire.panel.sources-page', compact('sources'));
    }
}
