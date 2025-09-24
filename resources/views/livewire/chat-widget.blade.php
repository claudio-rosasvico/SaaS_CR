{{-- <div>
  <div id="messages" class="mb-3" style="max-height: 60vh; overflow:auto;">
    @foreach ($messages as $m)
      <div class="mb-2">
        <strong class="{{ $m['role'] === 'user' ? 'text-primary' : 'text-success' }}">
          {{ $m['role'] === 'user' ? 'Tú' : 'Asistente' }}:
        </strong>
        <div class="whitespace-pre-wrap">{{ $m['content'] }}</div>
      </div>
    @endforeach

    
    <div id="assistant-live" style="display:none" class="mb-2">
      <strong class="text-success">Asistente:</strong>
      <div id="assistant-live-text" class="whitespace-pre-wrap"></div>
    </div>
  </div>

  <form id="chatForm" onsubmit="return handleStreamSubmit(event)">
    <div class="input-group">
      <textarea id="chatInput" class="form-control" rows="2" placeholder="Escribí tu pregunta..."></textarea>
      <button class="btn btn-primary">Enviar</button>
    </div>
  </form>
</div>
 --}}

<div class="card">
    <div class="card-body" style="max-height: 420px; overflow:auto;">
        @foreach ($this->messages as $m)
            <div class="mb-2">
                <strong class="text-{{ $m['role'] === 'user' ? 'primary' : 'success' }}">
                    {{ $m['role'] === 'user' ? 'Vos' : 'Asistente' }}:
                </strong>
                <div>{{ $m['content'] }}</div>
            </div>
        @endforeach
    </div>
    <div class="card-footer">
        <form wire:submit.prevent="send" class="d-flex gap-2">
            <input class="form-control" wire:model.defer="input" placeholder="Escribí tu mensaje...">
            <button class="btn btn-primary">Enviar</button>
        </form>
    </div>
</div>
@push('scripts')
    <script>
        function scrollToBottom() {
            const box = document.getElementById('messages');
            box.scrollTop = box.scrollHeight;
        }

        async function handleStreamSubmit(e) {
            e.preventDefault();

            const ta = document.getElementById('chatInput');
            const q = ta.value.trim();
            if (!q) return false;

            const messages = document.getElementById('messages');

            // pinta tu mensaje
            const userDiv = document.createElement('div');
            userDiv.className = 'mb-2';
            userDiv.innerHTML = '<strong class="text-primary">Tú:</strong><div class="whitespace-pre-wrap"></div>';
            userDiv.querySelector('div').textContent = q;
            messages.appendChild(userDiv);
            scrollToBottom();

            // burbuja live del asistente
            const live = document.getElementById('assistant-live');
            const liveText = document.getElementById('assistant-live-text');
            live.style.display = '';
            liveText.textContent = '';

            // POST con CSRF y conversation_id
            const resp = await fetch('{{ url('/stream-chat') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    q,
                    conversation_id: {{ $conversationId ?? 'null' }},
                    channel: 'web'
                })
            });

            const reader = resp.body.getReader();
            const dec = new TextDecoder();
            let done, value;
            while (({
                    done,
                    value
                } = await reader.read()) && !done) {
                liveText.textContent += dec.decode(value);
                scrollToBottom();
            }

            // al terminar, pedimos al componente que recargue desde DB
            if (window.Livewire) {
                window.Livewire.dispatch('refreshMessages');
            }
            ta.value = '';
            return false;
        }
    </script>
@endpush
