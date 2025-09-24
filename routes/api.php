<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Services\LlmGateway;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Api\ChatStreamController;
use App\Http\Controllers\Api\EmbedChatController;


Route::middleware(['throttle:60,1']) // rate limit básico
    ->post('/embed/chat', [EmbedChatController::class, 'chat'])
    ->name('api.embed.chat');
