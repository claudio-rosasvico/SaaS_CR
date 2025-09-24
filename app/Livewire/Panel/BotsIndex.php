<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Models\Bot;
use App\Models\ChannelIntegration;
use Illuminate\Support\Str;

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

    // Presentación
    public string $welcome_text = '';
    public string $suggested = ''; // coma-separados -> array

    // Tema
    public string $theme_primary = '#2563eb';
    public string $theme_position = 'br'; // br | bl
    public bool   $theme_rounded = true;

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

        $cfg = (array) ($b->config ?? []);
        $this->system_prompt  = (string)($cfg['system_prompt'] ?? '');
        $this->temperature    = (float) ($cfg['temperature'] ?? 0.2);
        $this->max_tokens     = (int)   ($cfg['max_tokens'] ?? 350);
        $this->language       = (string)($cfg['language'] ?? 'es');
        $this->citations      = (bool)  ($cfg['citations'] ?? false);
        $this->retrieval_mode = (string)($cfg['retrieval_mode'] ?? 'semantic');

        $pres = (array) ($cfg['presentation'] ?? []);
        $this->welcome_text = (string)($pres['welcome_text'] ?? '');
        $this->suggested    = implode(', ', (array)($pres['suggested'] ?? []));

        $theme = (array) ($b->embed_theme ?? []);
        $this->theme_primary  = (string)($theme['primary'] ?? '#2563eb');
        $this->theme_position = (string)($theme['position'] ?? 'br');
        $this->theme_rounded  = (bool)  ($theme['rounded'] ?? true);

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

            // Presentación
            'welcome_text'   => 'nullable|string|max:500',
            'suggested'      => 'nullable|string|max:500',

            // Tema
            'theme_primary'  => 'required|string|max:20',
            'theme_position' => 'required|in:br,bl',
            'theme_rounded'  => 'boolean',
        ];
        if ($this->channel === 'telegram') {
            $rules['token'] = 'required|string|max:200';
        } else {
            $rules['token'] = 'nullable|string|max:200';
        }
        $this->validate($rules);

        $pres = [
            'welcome_text' => $this->welcome_text,
            'suggested'    => $this->parseSuggested($this->suggested),
        ];
        $theme = [
            'primary'  => $this->theme_primary,
            'position' => $this->theme_position,
            'rounded'  => (bool)$this->theme_rounded,
        ];

        $data = [
            'organization_id' => current_org_id(),
            'name'            => $this->name,
            'channel'         => $this->channel,
            'is_default'      => $this->is_default,
            'config' => [
                'system_prompt'  => $this->system_prompt,
                'temperature'    => $this->temperature,
                'max_tokens'     => $this->max_tokens,
                'language'       => $this->language,
                'citations'      => $this->citations,
                'retrieval_mode' => $this->retrieval_mode,
                'presentation'   => $pres,
            ],
            'embed_theme' => $theme,
        ];

        if ($this->editId) {
            $b = Bot::where('organization_id', current_org_id())->findOrFail($this->editId);
            $b->fill($data)->save();
        } else {
            $b = Bot::create($data);
        }

        // Asegurar public_key si es web
        if ($b->channel === 'web' && empty($b->public_key)) {
            $b->public_key = Str::random(40);
            $b->save();
        }

        // Token Telegram si corresponde
        if ($b->channel === 'telegram') {
            ChannelIntegration::updateOrCreate(
                ['organization_id' => current_org_id(), 'channel' => 'telegram'],
                ['enabled' => true, 'config' => ['token' => $this->token]]
            );
        }

        // Único default por canal
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

    public function generateKey(int $id): void
    {
        $b = Bot::where('organization_id', current_org_id())->findOrFail($id);
        if ($b->channel !== 'web') {
            session()->flash('ok', 'Solo los bots del canal web usan public_key.');
            return;
        }
        if (empty($b->public_key)) {
            $b->public_key = Str::random(40);
            $b->save();
            $this->loadItems();
            session()->flash('ok', 'Public key generada.');
        }
    }

    private function parseSuggested(string $s): array
    {
        if (trim($s) === '') return [];
        return array_values(array_filter(array_map(
            fn($x) => trim($x),
            explode(',', $s)
        )));
    }

    public function updatedChannel(): void
    {
        if ($this->channel === 'telegram') {
            $ci = ChannelIntegration::where('organization_id', current_org_id())
                ->where('channel', 'telegram')->first();
            $this->token = (string) data_get($ci, 'config.token', '');
        } else {
            $this->token = '';
        }
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
        $this->token = '';

        // presentación
        $this->welcome_text = '';
        $this->suggested = '';

        // tema
        $this->theme_primary  = '#2563eb';
        $this->theme_position = 'br';
        $this->theme_rounded  = true;
    }

    public function render()
    {
        return view('livewire.panel.bots-index')
        ->layout('panel.layout', ['title' => 'Bots']);
    }
}
