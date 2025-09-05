<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Message;
use App\Models\Conversation;
use App\Services\ChatService;

class ChatWidget extends Component
{
    public ?int $conversationId = null;
    public array $messages = [];
    public string $input = '';

    public function mount(ChatService $chat)
    {
        if ($this->conversationId) {
            $this->loadMessages();
            return;
        }

        $bot = ensure_default_bot();

        $conv = \App\Models\Conversation::create([
            'channel'          => 'web',
            'started_at'       => now(),
            'organization_id'  => current_org_id(),
            'bot_id'           => $bot->id,
        ]);

        $this->conversationId = $conv->id;
        $this->loadMessages();
    }

    // Envío “no streaming” (si lo querés conservar para pruebas)
    public function send(ChatService $chat)
    {
        $text = trim($this->input);
        if ($text === '') return;

        $chat->handle($this->conversationId, $text, 'web');
        $this->loadMessages();
        $this->input = '';
    }

    #[On('refreshMessages')]
    public function refreshMessages(): void
    {
        $this->loadMessages();
    }

    public function refresh(): void
    {
        $this->loadMessages();
    }

    protected function loadMessages(): void
    {
        if (!$this->conversationId) {
            $this->messages = [];
            return;
        }

        $this->messages = Message::where('conversation_id', $this->conversationId)
            ->orderBy('id')
            ->get(['role', 'content'])
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();
    }

    public function render()
    {
        return view('livewire.chat-widget');
    }
}
