<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Conversation;
use App\Services\ChatService;

class TelegramPoll extends Command
{
    // app/Console/Commands/TelegramPoll.php
    protected $signature = 'telegram:poll {orgId?} {--timeout=30}';

    public function handle()
    {
        $orgId = $this->argument('orgId');
        if (!$orgId) {
            $this->error('Usa: php artisan telegram:poll {orgId}');
            return;
        }

        $ci = \App\Models\ChannelIntegration::where('organization_id', $orgId)
            ->where('channel', 'telegram')->where('enabled', true)->first();

        $token = data_get($ci, 'config.token') ?? env('TELEGRAM_BOT_TOKEN');
        if (!$token) {
            $this->error("Sin token para org {$orgId}");
            return;
        }

        $offset = $ci->config['last_update_id'] ?? null;
        $timeout = (int) $this->option('timeout');

        $this->info("Polling org={$orgId}...");

        while (true) {
            $resp = \Http::timeout($timeout + 5)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                'timeout' => $timeout,
                'offset'  => $offset ? $offset + 1 : null,
            ]);

            if (!$resp->ok()) {
                sleep(2);
                continue;
            }

            foreach ($resp->json('result', []) as $u) {
                $offset = $u['update_id'] ?? $offset;
                // procesar mensaje...
                $text = data_get($u, 'message.text');
                $chatId = data_get($u, 'message.chat.id');
                if (!$text || !$chatId) continue;

                // crea/usa conversación en esta org y canal 'telegram'
                $conv = \App\Models\Conversation::firstOrCreate(
                    ['organization_id' => $orgId, 'channel' => 'telegram', 'external_id' => (string)$chatId],
                    ['started_at' => now()]
                );

                // delegá al ChatService / ChatStream (sin stream) o tu pipeline actual:
                $svc = app(\App\Services\ChatService::class);
                $res = $svc->handle($conv->id, $text, 'telegram');

                // responde a Telegram
                $reply = last($res['messages'])['content'] ?? '...';
                \Http::asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text'    => $reply,
                ]);
            }

            // guarda offset
            $cfg = $ci->config;
            $cfg['last_update_id'] = $offset;
            $ci->update(['config' => $cfg]);
        }
    }
}
