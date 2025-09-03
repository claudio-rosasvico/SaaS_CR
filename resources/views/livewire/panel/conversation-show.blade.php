<x-slot:title>Conversación #{{ $conversation->id }}</x-slot:title>

<div class="row g-3">
  <div class="col-12">
    <a href="{{ route('panel.conversations') }}" class="btn btn-sm btn-outline-secondary">&larr; Volver</a>
  </div>
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Conversación #{{ $conversation->id }}</h5>
        <div class="vstack gap-2">
          @foreach($conversation->messages as $m)
            <div class="p-2 border rounded {{ $m->role === 'assistant' ? 'bg-light' : '' }}">
              <div class="small text-muted mb-1">
                {{ ucfirst($m->role) }} • {{ $m->created_at->format('d/m/Y H:i') }}
              </div>
              <div>{{ $m->content }}</div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>
