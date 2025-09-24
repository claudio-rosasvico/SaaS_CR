<div>
    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Bots de la organización</h5>
        <button class="btn btn-primary btn-sm" wire:click="createNew">Nuevo bot</button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Canal</th>
                    <th>Default</th>
                    <th>Temp</th>
                    <th>MaxTok</th>
                    <th style="min-width:280px">Public Key (web)</th>
                    <th class="text-end">Acciones</th>
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

                        <td>
                            @if ($b->channel === 'web' && !empty($b->public_key))
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" value="{{ $b->public_key }}" readonly>
                                    <button class="btn btn-outline-secondary" type="button"
                                        onclick="navigator.clipboard.writeText('{{ $b->public_key }}')">
                                        Copiar
                                    </button>
                                    <button class="btn btn-outline-primary" type="button" title="Script para embeber" data-bs-toggle="tooltip"
                                        onclick="navigator.clipboard.writeText('<script defer src=&quot;{{ url('/widget.js') }}?key={{ $b->public_key }}&quot;></'+'script>')">
                                        Copiar script
                                    </button>
                                </div>
                            @elseif($b->channel === 'web')
                                <button class="btn btn-sm btn-outline-warning"
                                    wire:click="generateKey({{ $b->id }})">
                                    Generar key
                                </button>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary"
                                    wire:click="edit({{ $b->id }})">Editar</button>
                                <button class="btn btn-outline-info"
                                    wire:click="makeDefault({{ $b->id }})">Hacer default</button>
                                <button class="btn btn-outline-danger"
                                    wire:click="delete({{ $b->id }})">Borrar</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-muted">Sin bots aún.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <script defer src="http://localhost:8080/widget.js?key=ahTrWM2l1ziDfLQsiRHJDdCnR0pS29gTVCoDzWPW"></script>

    @if ($modal)
        <div>
            <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background:rgba(0,0,0,.3)">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <form wire:submit.prevent="save">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ $editId ? 'Editar bot' : 'Nuevo bot' }}</h5>
                                <button type="button" class="btn-close" wire:click="$set('modal', false)"></button>
                            </div>

                            <div class="modal-body">
                                <ul class="nav nav-tabs mb-3" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-core"
                                            role="tab">Básico</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#tab-pres"
                                            role="tab">Presentación</a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <!-- Básico -->
                                    <div class="tab-pane fade show active" id="tab-core" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Nombre</label>
                                                <input class="form-control" wire:model.defer="name">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Canal</label>
                                                <select class="form-select" wire:model.defer="channel">
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
                                                <textarea class="form-control" rows="4" wire:model.defer="system_prompt"></textarea>
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Temperatura</label>
                                                <input type="number" step="0.1" min="0" max="1"
                                                    class="form-control" wire:model.defer="temperature">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Máx. tokens</label>
                                                <input type="number" min="64" max="2048"
                                                    class="form-control" wire:model.defer="max_tokens">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Idioma</label>
                                                <input class="form-control" wire:model.defer="language"
                                                    placeholder="es">
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
                                                    <label class="form-check-label" for="cit">Forzar
                                                        citas</label>
                                                </div>
                                            </div>

                                            <div class="col-9"
                                                @if ($channel !== 'telegram') style="display:none" @endif>
                                                <label class="form-label">Token (Telegram)</label>
                                                <input type="text" class="form-control" wire:model.defer="token"
                                                    placeholder="123456:ABC...">
                                            </div>

                                            @if ($channel === 'web' && isset($editId))
                                                <div class="col-12">
                                                    <div
                                                        class="alert alert-info d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <div><strong>Public Key</strong> para embeber:
                                                                <code>{{ optional(\App\Models\Bot::find($editId))->public_key ?? '—' }}</code>
                                                            </div>
                                                            <small>Usá el botón “Copiar script” en la tabla para pegarlo
                                                                en tu sitio.</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Presentación -->
                                    <div class="tab-pane fade" id="tab-pres" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Mensaje de bienvenida</label>
                                                <input class="form-control" wire:model.defer="welcome_text"
                                                    placeholder="¡Hola! ¿En qué te ayudo?">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Sugerencias (separadas por coma)</label>
                                                <input class="form-control" wire:model.defer="suggested"
                                                    placeholder="Cómo puedo guiarte, Horarios de atención, Ubicación, etc.">
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">Color primario</label>
                                                <input class="form-control" wire:model.defer="theme_primary"
                                                    placeholder="#2563eb">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Posición botón</label>
                                                <select class="form-select" wire:model.defer="theme_position">
                                                    <option value="br">Abajo derecha</option>
                                                    <option value="bl">Abajo izquierda</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="thround"
                                                        wire:model.defer="theme_rounded">
                                                    <label class="form-check-label" for="thround">Bordes
                                                        redondeados</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div><!-- /tab-content -->
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
