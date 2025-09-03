<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\ChatService;

class ChatWidget extends Component
{
    public ?int $conversationId = null;
    public array $messages = [];
    public string $input = '';

    public function mount(ChatService $chat)
    {
        // Creamos la conversación al montar
        $resp = $chat->handle(null, '¡Hola!', 'web'); // mensaje semilla opcional
        $this->conversationId = $resp['conversation_id'];
        // No mostramos el “¡Hola!” del user semilla; sólo vaciamos
        \App\Models\Message::where('conversation_id', $this->conversationId)
            ->where('role', 'user')->where('content','¡Hola!')->delete();
    }

    public function send(ChatService $chat)
    {
        $text = trim($this->input);
        if ($text === '') return;

        $resp = $chat->handle($this->conversationId, $text, 'web');

        foreach ($resp['messages'] as $m) {
            // Evitamos duplicar si ya están (simple: sólo empujamos los últimos 2)
        }
        $lastTwo = array_slice($resp['messages'], -2);
        foreach ($lastTwo as $m) {
            $this->messages[] = ['role' => $m['role'], 'content' => $m['content']];
        }

        $this->input = '';
    }

    public function render()
    {
        return view('livewire.chat-widget');
    }
}
