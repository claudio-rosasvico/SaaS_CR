<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\TelegramBot;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;

class TelegramPollAll extends Command
{
    protected $signature = 'telegram:poll-all {--timeout=25}';
    protected $description = 'Long polling para TODOS los bots de Telegram habilitados';

    public function handle(ChatService $chat): int
    {
        $timeout = (int)$this->option('timeout');

        while (true) {
            $bots = TelegramBot::where('is_enabled', true)->get();
            foreach ($bots as $bot) {
                $offset = $bot->last_update_id ? $bot->last_update_id + 1 : null;
                $url = "https://api.telegram.org/bot{$bot->token}/getUpdates";
                $res = Http::retry(2, 200)->get($url, array_filter([
                    'timeout' => $timeout,
                    'offset'  => $offset,
                ]));
                if (!$res->ok()) continue;

                $updates = $res->json('result') ?? [];
                foreach ($updates as $u) {
                    $bot->last_update_id = $u['update_id'];
                    $bot->save();

                    $msg = $u['message'] ?? $u['edited_message'] ?? null;
                    if (!$msg) continue;

                    $chatId = $msg['chat']['id'];
                    $text   = trim($msg['text'] ?? '');
                    if ($text === '') continue;

                    // conversación por chat+org
                    $conv = Conversation::firstOrCreate([
                        'organization_id' => $bot->organization_id,
                        'channel' => 'telegram',
                        'external_id' => (string)$chatId,
                    ], [
                        'started_at' => now(),
                        'bot_id'     => $bot->bot_id ?? ensure_default_bot('telegram')->id,
                    ]);

                    // Enviar a tu flujo normal (sin streaming en telegram por ahora)
                    $resp = $chat->handle($conv->id, $text, 'telegram');

                    // Responder al chat
                    $reply = collect($resp['messages'])->last()['content'] ?? '…';
                    $sendUrl = "https://api.telegram.org/bot{$bot->token}/sendMessage";
                    Http::post($sendUrl, [
                        'chat_id' => $chatId,
                        'text'    => $reply,
                    ]);
                }
            }
        }

        return self::SUCCESS;
    }
}
