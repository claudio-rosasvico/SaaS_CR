<div class="chat-widget" style="max-width:360px;border:1px solid #ddd;border-radius:10px;overflow:hidden;font-family:system-ui;">
    <div style="background:#0d6efd;color:#fff;padding:10px;">{{ config('app.name') }}</div>

    <div style="height:300px; overflow:auto; padding:10px;">
        @foreach($messages as $m)
            <div style="margin-bottom:8px;">
                <strong style="text-transform:capitalize">{{ $m['role'] }}:</strong>
                <span>{{ $m['content'] }}</span>
            </div>
        @endforeach
    </div>

    <form wire:submit="send" style="display:flex; gap:6px; padding:10px; border-top:1px solid #eee;">
        <input wire:model="input" type="text" placeholder="Escribí tu mensaje..."
               style="flex:1; padding:8px; border:1px solid #ccc; border-radius:6px;">
        <button type="submit" style="padding:8px 12px; background:#0d6efd; color:#fff; border:none; border-radius:6px;">
            Enviar
        </button>
    </form>
</div>
