<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $bot->name ?? 'Chatbot' }}</title>
    <style>
        :root {
            --cb-primary: {{ data_get($theme, 'primary', '#1f6feb') }};
            --cb-bg: {{ data_get($theme, 'bg', '#ffffff') }};
            --cb-text: {{ data_get($theme, 'text', '#111') }};
        }

        * {
            box-sizing: border-box;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, sans-serif
        }

        body {
            margin: 0;
            background: var(--cb-bg);
            color: var(--cb-text)
        }

        .chat-wrap {
            display: flex;
            flex-direction: column;
            height: 100vh;
            max-height: 100vh
        }

        .chat-header {
            padding: 12px 16px;
            background: var(--cb-primary);
            color: #fff
        }

        .chat-body {
            flex: 1;
            overflow: auto;
            padding: 12px 10px
        }

        .msg {
            margin: 6px 0;
            max-width: 90%
        }

        .msg.user {
            margin-left: auto;
            background: #e8f0ff
        }

        .msg.assistant {
            margin-right: auto;
            background: #f5f5f5
        }

        .msg .bubble {
            padding: 10px 12px;
            border-radius: 10px;
            white-space: pre-wrap
        }

        .chat-input {
            display: flex;
            gap: 8px;
            padding: 10px;
            border-top: 1px solid #eee
        }

        .chat-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px
        }

        .chat-input button {
            padding: 10px 14px;
            border: 0;
            border-radius: 6px;
            background: var(--cb-primary);
            color: #fff;
            cursor: pointer
        }

        .muted {
            opacity: .55
        }
    </style>
</head>

<body>
    <div class="chat-wrap" id="app">
        <div class="chat-header">
            <strong>{{ $bot->name }}</strong>
        </div>

        <div class="chat-body" id="messages">
            <div class="msg assistant">
                <div class="bubble muted">¡Hola!!! ¿En qué te ayudo?</div>
            </div>
        </div>

        <div class="chat-input">
            <input id="q" type="text" placeholder="Escribí tu mensaje..." autocomplete="off">
            <button id="send">Enviar</button>
        </div>
    </div>

    <script>
        (function() {
            const API = "{{ route('api.embed.chat') }}";
            const BOT_KEY = @json($key);
            let conversationId = null;

            const $msgs = document.getElementById('messages');
            const $q = document.getElementById('q');
            const $send = document.getElementById('send');

            function add(role, text, muted = false) {
                const div = document.createElement('div');
                div.className = 'msg ' + role;
                div.innerHTML = '<div class="bubble' + (muted ? ' muted' : '') + '"></div>';
                div.querySelector('.bubble').textContent = text;
                $msgs.appendChild(div);
                $msgs.scrollTop = $msgs.scrollHeight;
            }

            async function ask() {
                const text = $q.value.trim();
                if (!text) return;
                add('user', text);
                $q.value = '';

                try {
                    const res = await fetch(API, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            bot_key: BOT_KEY,
                            q: text,
                            conversation_id: conversationId
                        })
                    });
                    if (!res.ok) {
                        add('assistant', '(Error de servidor)');
                        return;
                    }
                    const data = await res.json();
                    conversationId = data.conversation_id;
                    const last = (data.messages || []).slice(-1)[0];
                    add('assistant', last?.content || '(sin contenido)');
                } catch (e) {
                    add('assistant', '(No se pudo conectar)');
                }
            }

            $send.addEventListener('click', ask);
            $q.addEventListener('keydown', e => {
                if (e.key === 'Enter') ask();
            });
        })();
    </script>
</body>

</html>
