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

                        <button class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Guardar</span>
                            <span wire:loading wire:target="save"><span class="spinner-border spinner-border-sm"></span>
                                Guardando…</span>
                        </button>

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
                                    <th>Progreso</th>
                                    <th>Acciones</th>

                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sources as $s)
                                    <tr>
                                        <td>{{ $s->id }}</td>
                                        <td>{{ $s->title ?? '—' }}</td>
                                        <td><span class="badge bg-secondary">{{ $s->type }}</span></td>
                                        <td>
                                            @php
                                                $map = [
                                                    'ready' => 'success',
                                                    'processing' => 'warning',
                                                    'queued' => 'secondary',
                                                    'embedding' => 'info',
                                                    'error' => 'danger',
                                                ];
                                                $color = $map[$s->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge text-bg-{{ $color }}">{{ $s->status }}</span>
                                        </td>
                                        <td style="min-width:160px">
                                            @php
                                                $cc = (int) ($s->chunks_count ?? 0);
                                                $ec = (int) ($s->embedded_count ?? 0);
                                                $pct = $cc > 0 ? min(100, (int) round(($ec * 100) / $cc)) : 0;
                                                $badge =
                                                    $s->status === 'ready'
                                                        ? 'success'
                                                        : ($s->status === 'error'
                                                            ? 'danger'
                                                            : 'warning');
                                            @endphp
                                            <div class="progress" role="progressbar" aria-valuenow="{{ $pct }}"
                                                aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar bg-{{ $badge }}"
                                                    style="width: {{ $pct }}%">{{ $pct }}%</div>
                                            </div>
                                            @if (!empty($s->error))
                                                <div class="text-danger small mt-1">{{ $s->error }}</div>
                                            @endif
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
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox me-1"></i>
                                            Aún no cargaste fuentes. Subí un PDF o ingresá una URL para empezar.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $sources->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
