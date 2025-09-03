<?php

namespace App\Livewire\Panel;

use App\Models\Conversation;
use Livewire\Component;
use Livewire\WithPagination;

class ConversationsPage extends Component
{
    use WithPagination;

    public function render()
    {
        $conversations = Conversation::latest()
            ->withCount('messages')
            ->paginate(12);

        return view('livewire.panel.conversations-page', [
            'conversations' => $conversations
        ]);
    }
}
