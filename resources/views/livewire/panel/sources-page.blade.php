<div>
    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Nueva fuente</h5>

                    <form wire:submit="save" class="vstack gap-3" enctype="multipart/form-data">
                        <div>
                            <label class="form-label">Tipo</label>
                            <select class="form-select" wire:model.live="type">
                                <option value="text">Texto</option>
                                <option value="url">URL</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>

                        @switch($type)
                            @case('text')
                                <div wire:key="type-text">
                                    <label class="form-label">Contenido</label>
                                    <textarea class="form-control" rows="6" wire:model.defer="text_content"></textarea>
                                </div>
                            @break

                            @case('url')
                                <div wire:key="type-url">
                                    <label class="form-label">URL</label>
                                    <input type="url" class="form-control" placeholder="https://..." wire:model.defer="url">
                                </div>
                            @break

                            @case('pdf')
                                <div wire:key="type-pdf">
                                    <label class="form-label">Archivo PDF</label>
                                    <input type="file" class="form-control" wire:model="pdf" accept="application/pdf">
                                    @error('pdf')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            @break

                        @endswitch

                        <button class="btn btn-primary">Guardar</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Fuentes</h5>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sources as $s)
                                    <tr>
                                        <td>{{ $s->id }}</td>
                                        <td>{{ $s->title ?? '—' }}</td>
                                        <td><span class="badge bg-secondary">{{ $s->type }}</span></td>
                                        <td>
                                            @php $color = $s->status === 'ready' ? 'success' : ($s->status === 'error' ? 'danger' : 'warning'); @endphp
                                            <span class="badge bg-{{ $color }}">{{ $s->status }}</span>
                                        </td>
                                        <td>
                                            @if ($s->status !== 'ready')
                                                <button wire:click="process({{ $s->id }})"
                                                    class="btn btn-sm btn-outline-primary">
                                                    Procesar
                                                </button>
                                            @else
                                                <span class="text-muted small">Procesada</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $sources->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
