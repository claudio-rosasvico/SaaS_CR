<?php

namespace App\Http\Controllers\Embed;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use App\Models\Bot;
use App\Http\Controllers\Controller;

class WidgetScriptController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $publicKey = (string) $request->query('key', '');
            if ($publicKey === '') {
                return $this->js('// widget.js: falta ?key=PUBLIC_KEY', 400);
            }

            $bot = Bot::where('public_key', $publicKey)->first();

            if (!$bot) {
                return $this->js('// widget.js: bot no encontrado', 404);
            }
            if ($bot->channel !== 'web') {
                return $this->js('// widget.js: el bot no es de canal web', 400);
            }

            $theme    = (array) ($bot->embed_theme ?? []);
            $primary  = (string) ($theme['primary']  ?? '#2563eb');
            $position = (string) ($theme['position'] ?? 'br'); // br | bl
            $rounded  = (bool)   ($theme['rounded']  ?? true);

            // Armamos la URL del iframe del chat
            $iframeUrl = route('embed.widget', ['publicKey' => $publicKey]);

            // CSS (sin helpers privados, todo inline)
            $radius = $rounded ? '14px' : '4px';
            $css = <<<CSS
.cbw-wrap{all:initial;position:fixed;z-index:2147483647;}
.cbw-btn{all:unset;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;background:{$primary};color:#fff;border-radius:999px;width:56px;height:56px;box-shadow:0 6px 20px rgba(0,0,0,.18);font-size:24px;line-height:1;}
.cbw-panel{position:fixed;bottom:92px;right:20px;width:360px;max-width:90vw;height:560px;max-height:80vh;background:#fff;border:1px solid #e5e7eb;border-radius:{$radius};box-shadow:0 10px 30px rgba(0,0,0,.25);overflow:hidden;display:none;}
.cbw-iframe{border:0;width:100%;height:100%;}
.cbw-close{position:absolute;top:8px;right:8px;background:rgba(0,0,0,.55);color:#fff;border:0;border-radius:999px;width:28px;height:28px;cursor:pointer;font-size:16px;line-height:1;}
.cbw-badge{position:absolute;bottom:62px;right:20px;font:12px/1.3 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;color:#64748b;}
CSS;

            if ($position === 'bl') {
                $css .= ".cbw-panel{left:20px;right:auto}\n.cbw-badge{left:20px;right:auto}\n";
            }

            $cssJson = json_encode($css, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $iframeUrlJson = json_encode($iframeUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $anchorSide = ($position === 'bl') ? 'left' : 'right';

            $js = <<<JS
(function(){
  try{
    var D = document;
    if (D.getElementById('cbw-wrap')) return;

    var wrap = D.createElement('div');
    wrap.id = 'cbw-wrap';
    wrap.className = 'cbw-wrap';
    wrap.style.bottom = '20px';
    wrap.style.{$anchorSide} = '20px';

    var style = D.createElement('style');
    style.textContent = {$cssJson};
    wrap.appendChild(style);

    var btn = D.createElement('button');
    btn.className = 'cbw-btn';
    btn.title = 'Chatear';
    btn.setAttribute('aria-label','Abrir chat');
    btn.innerHTML = '💬';
    wrap.appendChild(btn);

    var panel = D.createElement('div');
    panel.className = 'cbw-panel';
    wrap.appendChild(panel);

    var iframe = D.createElement('iframe');
    iframe.className = 'cbw-iframe';
    iframe.loading = 'lazy';
    iframe.referrerPolicy = 'no-referrer';
    iframe.src = {$iframeUrlJson};
    panel.appendChild(iframe);

    var close = D.createElement('button');
    close.className = 'cbw-close';
    close.innerHTML = '✕';
    close.title = 'Cerrar';
    close.onclick = function(){ panel.style.display='none'; };
    panel.appendChild(close);

    var badge = D.createElement('div');
    badge.className = 'cbw-badge';
    badge.textContent = '';
    wrap.appendChild(badge);

    btn.addEventListener('click', function(){
      panel.style.display = (panel.style.display==='none' || panel.style.display==='') ? 'block' : 'none';
    });

    D.body.appendChild(wrap);
  }catch(e){
    console && console.warn && console.warn('Widget error:', e);
  }
})();
JS;

            return response($js, 200, [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'public, max-age=300',
            ]);
        } catch (\Throwable $e) {
            Log::error('widget.js error', ['e' => $e->getMessage()]);
            return $this->js('// widget.js: error interno', 200); // no romper embebido
        }
    }

    private function js(string $body, int $status = 200)
    {
        return Response::make($body, $status, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
