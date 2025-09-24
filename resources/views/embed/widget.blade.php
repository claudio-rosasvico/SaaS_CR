<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ $botName }} — Chat</title>
  <style>
    :root{
      --cb-primary: {{ $primary }};
      --cb-radius: {{ $rounded ? '14px' : '4px' }};
    }
    *{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif}
    html,body{height:100%;margin:0}
    .wrap{display:flex;flex-direction:column;height:100%;background:#fff}
    .messages{flex:1;overflow:auto;padding:14px;background:#f8fafc}
    .bubble{max-width:85%;margin:8px 0;padding:10px 12px;border-radius:var(--cb-radius);line-height:1.35;white-space:pre-wrap}
    .me{background:#e1f5fe;margin-left:auto}
    .bot{background:#fff;border:1px solid #e5e7eb}
    .inputbar{display:flex;gap:8px;border-top:1px solid #e5e7eb;padding:8px;background:#fff}
    .inputbar input{flex:1;padding:10px;border:1px solid #d1d5db;border-radius:var(--cb-radius);font-size:14px}
    .inputbar button{padding:10px 14px;border:0;border-radius:var(--cb-radius);background:var(--cb-primary);color:#fff;font-weight:600;cursor:pointer}
    .suggests{display:flex;gap:8px;flex-wrap:wrap;padding:0 14px 10px}
    .chip{background:#eef2ff;border:1px solid #e5e7eb;border-radius:999px;padding:6px 10px;font-size:12px;cursor:pointer}
  </style>
</head>
<body>
  <div class="wrap">
    <div id="msgs" class="messages"></div>
    <div id="sugs" class="suggests"></div>
    <form id="f" class="inputbar">
      <input id="q" autocomplete="off" placeholder="Escribí tu consulta...">
      <button type="submit">Enviar</button>
    </form>
  </div>

  <script>
    (function(){
      const API   = '{{ route('api.embed.chat') }}';
      const PKEY  = @json($publicKey);
      const STORE = 'cb_conv_'+PKEY;

      const msgs = document.getElementById('msgs');
      const sugs = document.getElementById('sugs');
      const form = document.getElementById('f');
      const inp  = document.getElementById('q');

      let conversation_id = null;

      // restaurar conversación
      try {
        const saved = localStorage.getItem(STORE);
        if (saved) conversation_id = JSON.parse(saved);
      } catch(e){}

      // bienvenida
      add('assistant', @json($welcomeText));

      // sugerencias
      const suggested = @json($suggested);
      if (Array.isArray(suggested) && suggested.length){
        suggested.forEach(t => {
          const c = document.createElement('button');
          c.type = 'button';
          c.className = 'chip';
          c.textContent = t;
          c.addEventListener('click', ()=> { inp.value = t; form.dispatchEvent(new Event('submit',{cancelable:true})); });
          sugs.appendChild(c);
        });
      }

      form.addEventListener('submit', async (e)=>{
        e.preventDefault();
        const q = inp.value.trim();
        if (!q) return;
        add('user', q);
        inp.value = '';

        try{
          const r = await fetch(API, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ public_key: PKEY, q, conversation_id })
          });
          const j = await r.json();
          if (j.conversation_id){
            conversation_id = j.conversation_id;
            localStorage.setItem(STORE, JSON.stringify(conversation_id));
          }
          const last = (j.messages||[]).slice(-1)[0];
          add('assistant', (last && last.content) ? last.content : (j.answer || '…'));
        }catch(err){
          add('assistant', 'No pude responder ahora. Probá de nuevo.');
        }
      });

      function add(role, text){
        const b = document.createElement('div');
        b.className = 'bubble ' + (role === 'user' ? 'me' : 'bot');
        b.textContent = text;
        msgs.appendChild(b);
        msgs.scrollTop = msgs.scrollHeight;
      }
    })();
  </script>
</body>
</html>
