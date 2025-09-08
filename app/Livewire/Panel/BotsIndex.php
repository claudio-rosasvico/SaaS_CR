<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Models\Bot;

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
        $this->dispatch('open-bot-modal');
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
        $this->retrieval_mode= (string)($cfg['retrieval_mode'] ?? 'semantic');

        $this->dispatch('open-bot-modal');
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'channel' => 'required|in:web,telegram,whatsapp',
            'temperature' => 'numeric|min:0|max:1',
            'max_tokens' => 'integer|min:64|max:2048',
            'language' => 'required|string|max:10',
            'retrieval_mode' => 'required|in:semantic,keyword',
        ]);

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
                'retrieval_mode'=> $this->retrieval_mode,
            ],
        ];

        if ($this->editId) {
            $b = Bot::where('organization_id', current_org_id())->findOrFail($this->editId);
            $b->fill($data)->save();
        } else {
            $b = Bot::create($data);
        }

        // Garantizar único default por canal
        if ($b->is_default) {
            Bot::where('organization_id', current_org_id())
               ->where('channel', $b->channel)
               ->where('id', '!=', $b->id)
               ->update(['is_default' => false]);
        }

        $this->dispatch('close-bot-modal');
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
    }

    public function render()
    {
        return view('livewire.panel.bots-index');
    }
}
