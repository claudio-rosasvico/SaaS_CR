<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Models\Bot;
use App\Models\ChannelIntegration;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BotsIndex extends Component
{
    public $items = [];

    // Form
    public ?int $editId = null;
    public string $name = '';
    public string $channel = 'web';
    public bool $is_default = false;
    public string $system_prompt = '';
    public float $temperature = 0.2;
    public int $max_tokens = 350;
    public string $language = 'es';
    public bool $citations = false;
    public string $retrieval_mode = 'semantic';
    public string $token = '';
    public bool $modal = false;
    public bool $presentation_hide_urls  = true;
    public bool $presentation_hide_files = true;
    public int  $presentation_max_items  = 3;
    public string $presentation_domains_json = ''; // JSON dominio→alias
    public string $presentation_files_json   = ''; // JSON regex→alias
    public string $presentation_closure      = '¿Querés que te arme un mini itinerario?';

    public function mount(): void
    {
        $this->loadItems();
    }

    public function loadItems(): void
    {
        $this->items = Bot::where('organization_id', current_org_id())
            ->orderBy('channel')->orderByDesc('is_default')->orderBy('id')
            ->get()->all();
    }

    public function createNew(): void
    {
        $this->resetForm();
        $this->modal = true;
    }

    public function edit(int $id): void
    {
        $b = Bot::where('organization_id', current_org_id())->findOrFail($id);
        $this->editId = $b->id;
        $this->name = $b->name;
        $this->channel = $b->channel;
        $this->is_default = (bool)$b->is_default;
        $cfg = $b->config ?? [];
        $this->system_prompt = (string)($cfg['system_prompt'] ?? '');
        $this->temperature   = (float) ($cfg['temperature'] ?? 0.2);
        $this->max_tokens    = (int)   ($cfg['max_tokens'] ?? 350);
        $this->language      = (string)($cfg['language'] ?? 'es');
        $this->citations     = (bool)  ($cfg['citations'] ?? false);
        $this->retrieval_mode = (string)($cfg['retrieval_mode'] ?? 'semantic');

        $pres = (array) ($b->config['presentation'] ?? []);
        $this->presentation_hide_urls  = (bool)($pres['hide_urls']       ?? true);
        $this->presentation_hide_files = (bool)($pres['hide_file_names'] ?? true);
        $this->presentation_max_items  = (int) ($pres['max_items']       ?? 3);
        $this->presentation_closure    = (string)($pres['fallback_closure'] ?? '¿Querés que te arme un mini itinerario?');

        $domains = (array)($pres['aliases']['domains'] ?? []);
        $files   = (array)($pres['aliases']['files']   ?? []);
        $this->presentation_domains_json = $domains ? json_encode($domains, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '';
        $this->presentation_files_json   = $files   ? json_encode($files,   JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '';

        $this->token = '';
        if ($this->channel === 'telegram') {
            $ci = ChannelIntegration::where('organization_id', current_org_id())
                ->where('channel', 'telegram')->first();
            $this->token = (string) data_get($ci, 'config.token', '');
        }

        $this->modal = true;
    }

    public function save(): void
    {
        $rules = [
            'name'           => 'required|string|max:100',
            'channel'        => 'required|in:web,telegram,whatsapp',
            'temperature'    => 'numeric|min:0|max:1',
            'max_tokens'     => 'integer|min:64|max:2048',
            'language'       => 'required|string|max:10',
            'retrieval_mode' => 'required|in:semantic,keyword',
            'token'          => 'nullable|string|max:200',
            'presentation_max_items' => 'integer|min:1|max:10',
            'presentation_closure'   => 'nullable|string|max:200',
            'presentation_domains_json' => 'nullable|string',
            'presentation_files_json'   => 'nullable|string',
        ];
        Log::info("Validacion 1 hecha");
        if ($this->channel === 'telegram') {
            $rules['token'] = 'required|string|max:200';
        }
        $this->validate($rules);
        Log::info("Validacion confirmada");

        $domains = $this->parseJsonMap($this->presentation_domains_json, 'presentation_domains_json');
        $files   = $this->parseJsonMap($this->presentation_files_json,   'presentation_files_json');

        $presentation = [
            'hide_urls'        => (bool)$this->presentation_hide_urls,
            'hide_file_names'  => (bool)$this->presentation_hide_files,
            'max_items'        => (int)$this->presentation_max_items,
            'fallback_closure' => (string)($this->presentation_closure ?? ''),
            'aliases' => [
                'domains' => $domains,
                'files'   => $files,
            ],
        ];

        $data = [
            'organization_id' => current_org_id(),
            'name' => $this->name,
            'channel' => $this->channel,
            'is_default' => $this->is_default,
            'config' => [
                'system_prompt' => $this->system_prompt,
                'temperature'   => $this->temperature,
                'max_tokens'    => $this->max_tokens,
                'language'      => $this->language,
                'citations'     => $this->citations,
                'retrieval_mode' => $this->retrieval_mode,
            ],
        ];

        if ($this->editId) {
            $b = Bot::where('organization_id', current_org_id())->findOrFail($this->editId);
            $b->fill($data)->save();
        } else {
            $b = Bot::create($data);
        }
        Log::info("Bot creado: " . $b);
        if ($b->channel === 'telegram') {
            ChannelIntegration::updateOrCreate(
                ['organization_id' => current_org_id(), 'channel' => 'telegram'],
                ['enabled' => true, 'config' => ['token' => $this->token]]
            );
        }
        Log::info("Paso por el token");
        // Garantizar único default por canal
        if ($b->is_default) {
            Bot::where('organization_id', current_org_id())
                ->where('channel', $b->channel)
                ->where('id', '!=', $b->id)
                ->update(['is_default' => false]);
        }

        cache()->forget('bot:' . current_org_id() . ':' . $b->channel);

        $this->modal = false;
        $this->loadItems();
        session()->flash('ok', 'Bot guardado.');
    }

    public function makeDefault(int $id): void
    {
        $b = Bot::where('organization_id', current_org_id())->findOrFail($id);
        Bot::where('organization_id', current_org_id())
            ->where('channel', $b->channel)
            ->update(['is_default' => false]);
        $b->is_default = true;
        $b->save();
        $this->loadItems();
    }

    public function delete(int $id): void
    {
        $b = Bot::where('organization_id', current_org_id())->findOrFail($id);
        $b->delete();
        $this->loadItems();
    }

    protected function resetForm(): void
    {
        $this->editId = null;
        $this->name = '';
        $this->channel = 'web';
        $this->is_default = false;
        $this->system_prompt = '';
        $this->temperature = 0.2;
        $this->max_tokens = 350;
        $this->language = 'es';
        $this->citations = false;
        $this->retrieval_mode = 'semantic';
        $this->token = '';           // 👈 AÑADIR
        $this->presentation_hide_urls  = true;
        $this->presentation_hide_files = true;
        $this->presentation_max_items  = 3;
        $this->presentation_domains_json = '';
        $this->presentation_files_json   = '';
        $this->presentation_closure      = '¿Querés que te arme un mini itinerario?';
    }

    private function parseJsonMap(?string $json, string $field): array
    {
        $json = trim((string)$json);
        if ($json === '') return [];
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw ValidationException::withMessages([
                $field => 'Debes ingresar un JSON objeto clave→valor válido (ej: {"dominio.com":"Alias bonito"}).',
            ]);
        }
        $out = [];
        foreach ($data as $k => $v) {
            if (!is_string($k)) {
                throw ValidationException::withMessages([$field => 'Las claves deben ser texto.']);
            }
            if (!is_string($v) && !is_numeric($v)) {
                throw ValidationException::withMessages([$field => 'Los valores deben ser texto.']);
            }
            $out[(string)$k] = (string)$v;
        }
        return $out;
    }

    public function updatedChannel(): void
    {
        // Si el user cambia canal a Telegram en el modal, pre-cargá token si existe
        if ($this->channel === 'telegram') {
            $ci = ChannelIntegration::where('organization_id', current_org_id())
                ->where('channel', 'telegram')->first();
            $this->token = (string) data_get($ci, 'config.token', '');
        } else {
            $this->token = '';
        }
    }

    public function render()
    {
        return view('livewire.panel.bots-index');
    }
}
