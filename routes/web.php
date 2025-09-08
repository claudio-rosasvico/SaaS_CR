<?php

use App\Http\Controllers\Api\ChatStreamController;
use App\Http\Controllers\Panel\BillingController;
use App\Http\Controllers\Panel\MetricsController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Panel\ConversationShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::view('/panel', 'panel.dashboard')->name('panel.dashboard');
    Route::view('/panel/sources', 'panel.sources')->name('panel.sources');
    Route::view('/panel/conversations', 'panel.conversations')->name('panel.conversations');
    Route::get('/panel/conversations/{conversationId}', ConversationShow::class)
    ->name('panel.conversations.show');
    Route::get('/panel/metrics', [MetricsController::class, 'index'])->name('panel.metrics');
    Route::view('/panel/bot', 'panel.bot')->name('panel.bot');
    Route::view('/panel/bots', 'panel.bots')->name('panel.bots');

    Route::view('/chat', 'chat')->name('chat');
    Route::post('/stream-chat', ChatStreamController::class)->name('stream.chat');

    Route::get('/panel/billing', [BillingController::class, 'index'])->name('panel.billing');
});

require __DIR__.'/auth.php';
