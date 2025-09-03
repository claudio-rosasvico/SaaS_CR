<?php

namespace App\Livewire\Panel;

use App\Models\Conversation;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.panel')] // <-- clave
class ConversationShow extends Component
{
    public int $conversationId;

    public function mount(int $conversationId)
    {
        $this->conversationId = $conversationId;
    }

    public function render()
    {
        $conversation = Conversation::with(['messages' => fn($q) => $q->orderBy('id')])
            ->findOrFail($this->conversationId);

        return view('livewire.panel.conversation-show', [
            'conversation' => $conversation,
        ]);
    }
}
