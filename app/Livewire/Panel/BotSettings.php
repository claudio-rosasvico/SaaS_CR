<?php

namespace App\Livewire\Panel;

use Livewire\Component;
use App\Models\Bot;
use App\Models\ChannelIntegration;

class BotSettings extends Component
{
    // Campos editables
    public string $name = 'Demo Web';
    public string $channel = 'web';
    public string $system_prompt = '';
    public float  $temperature = 0.2;
    public int    $max_tokens = 400;
    public string $language = 'es';
    public bool   $citations = false;
    public string $retrieval_mode = '';
    public string $token = '';

    public ?int $botId = null;

    public function mount(): void
    {
        $bot = ensure_default_bot();    // helper que ya tenemos
        $this->botId = $bot->id;
        $cfg = $bot->config ?? [];

        $this->name          = $bot->name;
        $this->channel       = $bot->channel;
        $this->system_prompt = (string)($cfg['system_prompt'] ?? '');
        $this->temperature   = (float) ($cfg['temperature']   ?? 0.2);
        $this->max_tokens    = (int)   ($cfg['max_tokens']    ?? 400);
        $this->language      = (string)($cfg['language']      ?? 'es');
        $this->citations     = (bool)  ($cfg['citations']     ?? false);
        $this->retrieval_mode = (string)($cfg['retrieval_mode'] ?? env('RETRIEVAL_MODE', 'semantic'));
    }

    public function updatedChannel()
    {
        $bot = ensure_default_bot($this->channel);
        $cfg = $bot->config ?? [];
        $this->system_prompt = $cfg['system_prompt'] ?? '';
        $this->temperature   = $cfg['temperature']   ?? 0.2;
        $this->max_tokens    = $cfg['max_tokens']    ?? 400;
    }

    public function save(): void
    {
        $this->validate([
            'name'          => 'required|string|max:120',
            'channel'       => 'required|in:web,telegram,whatsapp',
            'system_prompt' => 'nullable|string|max:5000',
            'temperature'   => 'numeric|min:0|max:1',
            'max_tokens'    => 'integer|min:64|max:2048',
            'language'      => 'required|string|max:10',
            'retrieval_mode' => 'nullable|in:keyword,semantic',
            'token' => 'nullable|string|max:5000',
        ]);

        $bot = Bot::findOrFail($this->botId);
        $bot->name    = $this->name;
        $bot->channel = $this->channel;
        $cfg = $bot->config ?? [];
        $cfg['system_prompt'] = $this->system_prompt;
        $cfg['temperature']   = $this->temperature;
        $cfg['max_tokens']    = $this->max_tokens;
        $cfg['language']      = $this->language;
        $cfg['citations']     = $this->citations;
        $cfg['retrieval_mode'] = $this->retrieval_mode ?: ($cfg['retrieval_mode'] ?? env('RETRIEVAL_MODE', 'semantic'));

        $bot->config = $cfg;
        $bot->save();

        if ($this->token) {
            $channelIntegration = ChannelIntegration::updateOrCreate(
                ['channel' => $this->channel, ], // Criterios de búsqueda
                ['columna1' => 'valor_a_establecer', 'columna2' => 'otro_valor'] // Datos a actualizar o crear
            );
        }

        $orgId  = current_org_id();
        $channel = $this->channel; // o como lo tengas
        cache()->forget("bot:{$orgId}:{$channel}");
        session()->flash('ok', 'Configuración del bot guardada. Las próximas respuestas usarán esta personalidad.');
    }

    public function render()
    {
        return view('livewire.panel.bot-settings');
    }
}
