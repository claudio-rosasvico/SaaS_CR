<div>
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-3">Conversaciones</h5>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Canal</th>
              <th>Mensajes</th>
              <th>Inicio</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          @foreach($conversations as $c)
            <tr>
              <td>{{ $c->id }}</td>
              <td><span class="badge bg-info">{{ $c->channel }}</span></td>
              <td>{{ $c->messages_count }}</td>
              <td>{{ $c->started_at?->format('d/m/Y H:i') ?? $c->created_at->format('d/m/Y H:i') }}</td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('panel.conversations.show',$c->id) }}">
                  Ver
                </a>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      {{ $conversations->links() }}
    </div>
  </div>
</div>
