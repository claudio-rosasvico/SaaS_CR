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

    public function mount(\App\Services\ChatService $chat)
    {
        if ($this->conversationId) {
            $this->loadMessages();
            return;
        }

        // 👇 toma el bot default del canal web
        $bot = ensure_default_bot('web');

        $conv = Conversation::create([
            'channel'          => 'web',
            'started_at'       => now(),
            'organization_id'  => current_org_id(),
            'bot_id'           => $bot->id,   // 👈 clave
        ]);

        $this->conversationId = $conv->id;
        $this->loadMessages();
    }

    public function send(ChatService $chat)
    {
        {
        $text = trim($this->input);
        if ($text === '') return;

        $resp = $chat->handle($this->conversationId, $text, 'web');
        $this->conversationId = $resp['conversation_id'];

        $lastTwo = array_slice($resp['messages'], -2);
        foreach ($lastTwo as $m) {
            $this->messages[] = ['role' => $m['role'], 'content' => $m['content']];
        }

        $this->input = '';
    }
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
