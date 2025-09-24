<!doctype html>
<html lang="es">

<body style="font-family: Inter, Arial, sans-serif; background:#18252d; color:#fff; padding:24px;">
    <div
        style="max-width:620px; margin:auto; background:#0f171d; border:1px solid #2a3740; border-radius:12px; padding:22px;">
        <h2 style="margin:0 0 12px; font-family: Montserrat, Arial; font-weight:800;">Nuevo contacto</h2>
        <ul style="padding-left:16px; color:#cfd3d6;">
            <li><strong>Nombre:</strong> {{ $messageData->nombre }}</li>
            <li><strong>Email:</strong> {{ $messageData->email }}</li>
            <li><strong>Empresa:</strong> {{ $messageData->empresa ?: '—' }}</li>
            <li><strong>Servicio:</strong> {{ $messageData->servicio }}</li>
            <li><strong>IP:</strong> {{ $messageData->ip }}</li>
        </ul>
        @if ($messageData->mensaje)
            <p style="margin-top:10px; color:#e8edf1;">{{ $messageData->mensaje }}</p>
        @endif
        <p style="margin-top:18px;">
            <a href="{{ route('admin.messages.show', $messageData) }}"
                style="background:#f99f13;color:#1a1a1a;padding:10px 14px;border-radius:8px;text-decoration:none;font-weight:800;">
                Ver en panel
            </a>
        </p>
    </div>
</body>

</html>
