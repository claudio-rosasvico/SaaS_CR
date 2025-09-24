<!doctype html>
<html lang="es">

<body style="font-family: Inter, Arial, sans-serif; background:#18252d; color:#fff; padding:24px;">
    <div
        style="max-width:620px; margin:auto; background:#0f171d; border:1px solid #2a3740; border-radius:12px; padding:22px;">
        <h1 style="margin:0 0 8px; font-family: Montserrat, Arial; font-weight:800;">Shift+IA</h1>
        <p style="color:#cfd3d6;">Hola {{ $messageData->nombre }},</p>
        <p>¡Gracias por contactarte! Recibimos tu consulta sobre <strong>{{ $messageData->servicio }}</strong> y te
            responderemos a la brevedad.</p>
        @if ($messageData->mensaje)
            <blockquote style="border-left:4px solid #f99f13; padding-left:10px; color:#e8edf1;">
                {{ $messageData->mensaje }}
            </blockquote>
        @endif
        <p style="color:#cfd3d6;">Equipo Shift+IA — “Cambia de nivel con IA”</p>
    </div>
</body>

</html>
