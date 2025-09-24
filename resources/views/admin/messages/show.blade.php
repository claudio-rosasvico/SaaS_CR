@extends('layouts.panel')

@section('content')
    <div class="container py-4" style="max-width:960px;">
        @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
        @endif

        <a href="{{ route('admin.messages.index') }}" class="btn btn-secondary mb-3">← Volver</a>

        <div class="card bg-dark text-light mb-3" style="border-color:#374550">
            <div class="card-header" style="font-family:Montserrat;font-weight:800;">
                Mensaje #{{ $message->id }} — {{ ucfirst($message->status) }}
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Nombre</dt>
                    <dd class="col-sm-9">{{ $message->nombre }}</dd>
                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">{{ $message->email }}</dd>
                    <dt class="col-sm-3">Empresa</dt>
                    <dd class="col-sm-9">{{ $message->empresa ?: '—' }}</dd>
                    <dt class="col-sm-3">Servicio</dt>
                    <dd class="col-sm-9">{{ $message->servicio }}</dd>
                    <dt class="col-sm-3">Fecha</dt>
                    <dd class="col-sm-9">{{ $message->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="col-sm-3">IP</dt>
                    <dd class="col-sm-9">{{ $message->ip }}</dd>
                </dl>

                @if ($message->mensaje)
                    <hr>
                    <h5>Consulta</h5>
                    <p style="white-space:pre-wrap;">{{ $message->mensaje }}</p>
                @endif
            </div>
        </div>

        <div class="card bg-dark text-light" style="border-color:#374550">
            <div class="card-header" style="font-family:Montserrat;font-weight:800;">Responder</div>
            <div class="card-body">
                <form method="post" action="{{ route('admin.messages.reply', $message) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Asunto</label>
                        <input type="text" name="response_subject"
                            class="form-control @error('response_subject') is-invalid @enderror"
                            value="{{ old('response_subject', 'Sobre tu consulta en Shift+IA') }}" required>
                        @error('response_subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mensaje</label>
                        <textarea name="response_body" rows="8" class="form-control @error('response_body') is-invalid @enderror"
                            required>{{ old('response_body', "Hola {$message->nombre},\n\nGracias por escribirnos. Te comparto algunos detalles...\n\n— Equipo Shift+IA") }}</textarea>
                        @error('response_body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Respondido por (tu email/nombre)</label>
                        <input type="text" name="responded_by" class="form-control" value="{{ old('responded_by') }}">
                    </div>

                    <button class="btn btn-warning">Enviar respuesta</button>
                </form>

                @if ($message->status === 'respondido')
                    <hr>
                    <small class="text-muted">
                        Respondido el {{ optional($message->responded_at)->format('d/m/Y H:i') }} por
                        {{ $message->responded_by }}
                    </small>
                @endif
            </div>
        </div>
    </div>
@endsection
