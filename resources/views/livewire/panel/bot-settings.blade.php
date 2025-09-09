<div>
    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    <form wire:submit.prevent="save" class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre del bot</label>
                    <input type="text" class="form-control" wire:model.defer="name">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Canal</label>
                    <select class="form-select" wire:model.defer="channel">
                        <option value="web">Web</option>
                        <option value="telegram">Telegram</option>
                        <option value="whatsapp">WhatsApp</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Personalidad / Rol (system prompt)</label>
                    <textarea class="form-control" rows="6" wire:model.defer="system_prompt"
                        placeholder="Ej: Actúa como recepcionista médica empática. Responde en español, breve, y sugiere pasos seguros."></textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Temperatura</label>
                    <input type="number" step="0.1" min="0" max="1" class="form-control"
                        wire:model.defer="temperature">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Máx. tokens</label>
                    <input type="number" min="64" max="2048" class="form-control"
                        wire:model.defer="max_tokens">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Idioma</label>
                    <input type="text" class="form-control" wire:model.defer="language" placeholder="es">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Retrieval</label>
                    <select class="form-select" wire:model.defer="retrieval_mode">
                        <option value="">(por defecto)</option>
                        <option value="keyword">Keyword</option>
                        <option value="semantic">Semántico</option>
                    </select>
                </div>

                <div class="col-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="cit" wire:model.defer="citations">
                        <label class="form-check-label" for="cit">Forzar citas en la respuesta</label>
                    </div>
                </div>
                <div class="col-9" @if ($channel !== 'telegram') style="display:none" @endif>
                    <label class="form-label">Token (Telegram)</label>
                    <input type="text" class="form-control" wire:model.defer="token" placeholder="123456:ABC...">
                    <div class="form-text">
                        Se guarda por <strong>organización</strong> en Integraciones, no dentro del bot.
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary">Guardar</button>
        </div>
    </form>
</div>
