<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ChannelIntegration;
use App\Services\ChatService;

class TelegramPoll extends Command
{
    protected $signature = 'telegram:poll {orgId?} {--timeout=30}';
    protected $description = 'Long-poll de Telegram para una organización (canal=telegram)';

    public function handle()
    {
        $orgId   = $this->argument('orgId');
        $timeout = (int) $this->option('timeout');

        // Si lo llaman sin orgId (por error), NO tirar excepción: salir suave.
        if (!$orgId) {
            $this->warn('telegram:poll llamado sin {orgId}; saliendo sin ejecutar.');
            return Command::SUCCESS;
        }

        $orgId = (int) $orgId;

        $ci = ChannelIntegration::where('organization_id', $orgId)
            ->where('channel', 'telegram')
            ->where('enabled', true)
            ->first();

        if (!$ci) {
            $this->error("No hay ChannelIntegration habilitada para org {$orgId} (channel=telegram).");
            return Command::FAILURE;
        }

        $cfg   = $ci->config ?? [];
        $token = (string) data_get($cfg, 'token', '');
        if ($token === '') {
            $this->error("Sin token de Telegram para org {$orgId}.");
            return Command::FAILURE;
        }

        $offset = (int) data_get($cfg, 'last_update_id', 0);

        $this->info("Telegram poll ON  | org={$orgId} | timeout={$timeout}s");

        while (true) {
            try {
                $resp = Http::timeout($timeout + 5)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                    'timeout' => $timeout,
                    'offset'  => $offset ? ($offset + 1) : null,
                ]);

                if (!$resp->ok()) {
                    $this->warn("getUpdates HTTP {$resp->status()}");
                    sleep(2);
                    continue;
                }

                $updates = $resp->json('result', []);
                if (!is_array($updates) || empty($updates)) {
                    continue;
                }

                $maxUpdateId = $offset;

                foreach ($updates as $upd) {
                    $updId  = (int) ($upd['update_id'] ?? 0);
                    $maxUpdateId = max($maxUpdateId, $updId);

                    $msg    = $upd['message'] ?? null;
                    $chatId = data_get($msg, 'chat.id');
                    $text   = data_get($msg, 'text');

                    if (!$chatId || !is_string($text) || trim($text) === '') {
                        continue;
                    }

                    with_org($orgId, function () use ($ci, $chatId, $text, $token) {
                        $cfg = $ci->config ?? [];
                        $map = $cfg['chat_map'] ?? []; // chat_id => conversation_id

                        /** @var ChatService $svc */
                        $svc  = app(ChatService::class);
                        $resp = $svc->handle($map[(string)$chatId] ?? null, $text, 'telegram');

                        // actualizar mapeo chat->conversation
                        $map[(string)$chatId] = $resp['conversation_id'];
                        $ci->config = array_merge($cfg, ['chat_map' => $map]);
                        $ci->save();

                        // enviar respuesta
                        $reply = $resp['messages'][1]['content'] ?? '…';
                        Http::timeout(10)->retry(1, 200)->asForm()
                            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                                'chat_id' => $chatId,
                                'text'    => $reply,
                            ]);
                    });
                }

                // guardar offset si avanzó
                if ($maxUpdateId > $offset) {
                    $offset = $maxUpdateId;
                    $cfg = $ci->config ?? [];
                    $cfg['last_update_id'] = $offset;
                    $ci->update(['config' => $cfg]);
                }
            } catch (\Throwable $e) {
                \Log::error('TelegramPoll loop error', ['error' => $e->getMessage()]);
                sleep(2);
            }
        }
    }
}
