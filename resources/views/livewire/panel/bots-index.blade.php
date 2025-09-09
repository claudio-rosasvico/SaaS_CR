<div>
    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Bots de la organización</h5>
        <button class="btn btn-primary btn-sm" wire:click="createNew">Nuevo bot</button>
    </div>

    <table class="table table-sm align-middle">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Canal</th>
                <th>Default</th>
                <th>Temp</th>
                <th>MaxTok</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $b)
                @php $cfg = $b->config ?? []; @endphp
                <tr>
                    <td>{{ $b->name }}</td>
                    <td class="text-capitalize">{{ $b->channel }}</td>
                    <td>{!! $b->is_default ? '<span class="badge text-bg-success">Sí</span>' : '—' !!}</td>
                    <td>{{ $cfg['temperature'] ?? '—' }}</td>
                    <td>{{ $cfg['max_tokens'] ?? '—' }}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-secondary"
                            wire:click="edit({{ $b->id }})">Editar</button>
                        <button class="btn btn-sm btn-outline-info" wire:click="makeDefault({{ $b->id }})">Hacer
                            default</button>
                        <button class="btn btn-sm btn-outline-danger"
                            wire:click="delete({{ $b->id }})">Borrar</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-muted">Sin bots aún.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Modal simple (usa Bootstrap modal si querés; acá un modal liviano) --}}
    @if ($modal)
        <div>
            <div class="modal fade show d-block" tabindex="-1" role="dialog" x-show="open"
                style="background:rgba(0,0,0,.3)">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form wire:submit.prevent="save">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ $editId ? 'Editar bot' : 'Nuevo bot' }}</h5>
                                <button type="button" class="btn-close" wire:click="$set('modal', false)"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre</label>
                                        <input class="form-control" wire:model.defer="name">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Canal</label>
                                        <select class="form-select" wire:model.live="channel">
                                            <option value="web">Web</option>
                                            <option value="telegram">Telegram</option>
                                            <option value="whatsapp">WhatsApp</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="isdef"
                                                wire:model.defer="is_default">
                                            <label class="form-check-label" for="isdef">Default</label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Personalidad (system prompt)</label>
                                        <textarea class="form-control" rows="5" wire:model.defer="system_prompt"></textarea>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Temperatura</label>
                                        <input type="number" step="0.1" min="0" max="1"
                                            class="form-control" wire:model.defer="temperature">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Máx. tokens</label>
                                        <input type="number" min="64" max="2048" class="form-control"
                                            wire:model.defer="max_tokens">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Idioma</label>
                                        <input class="form-control" wire:model.defer="language" placeholder="es">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Retrieval</label>
                                        <select class="form-select" wire:model.defer="retrieval_mode">
                                            <option value="semantic">Semántico</option>
                                            <option value="keyword">Keyword</option>
                                        </select>
                                    </div>

                                    <div class="col-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="cit"
                                                wire:model.defer="citations">
                                            <label class="form-check-label" for="cit">Forzar citas</label>
                                        </div>
                                    </div>
                                    <div class="col-9" @if($channel !== 'telegram') style="display: none" @endif>
                                        <label class="form-label">Token (Telegram)</label>
                                        <input type="text" class="form-control" wire:model.live="token"
                                            placeholder="123456:ABC...">
                                    </div>

                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" type="button"
                                    wire:click="$set('modal', false)">Cancelar</button>
                                <button class="btn btn-primary" type="submit">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
