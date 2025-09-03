<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Conversation;
use App\Services\ChatService;

class TelegramPoll extends Command
{
    protected $signature = 'telegram:poll {--once}';
    protected $description = 'Long polling a Telegram Bot API (getUpdates)';

    public function handle(ChatService $chat)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) { $this->error('TELEGRAM_BOT_TOKEN vacío'); return 1; }

        $offset = Cache::get('tg_offset', 0);

        do {
            $res = Http::timeout(35)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                'timeout' => 30,
                'offset'  => $offset+1
            ]);
            if (!$res->ok()) { usleep(500000); continue; }

            $updates = $res->json('result') ?? [];
            foreach ($updates as $u) {
                $updateId = $u['update_id'];
                $msg = $u['message'] ?? $u['edited_message'] ?? null;
                if ($msg) {
                    $chatId = $msg['chat']['id'] ?? null;
                    $text   = $msg['text'] ?? '';
                    if ($chatId && $text !== '') {
                        $conv = Conversation::firstOrCreate(
                            ['channel' => 'telegram', 'external_id' => (string)$chatId],
                            ['started_at' => now()]
                        );
                        $resp = $chat->handle($conv->id, $text, 'telegram');
                        $assistant = end($resp['messages']);
                        Http::asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                            'chat_id' => $chatId,
                            'text' => $assistant['content'] ?? '...',
                            'parse_mode' => 'HTML',
                        ]);
                    }
                }
                $offset = $updateId;
            }

            Cache::put('tg_offset', $offset, 86400);
            if ($this->option('once')) break;
        } while(true);

        return 0;
    }
}
