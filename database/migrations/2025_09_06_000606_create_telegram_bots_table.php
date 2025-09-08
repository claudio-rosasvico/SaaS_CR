<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('telegram_bots', function (Blueprint $t) {
      $t->id();
      $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
      $t->foreignId('bot_id')->nullable()->constrained('bots')->nullOnDelete();
      $t->string('name')->nullable();
      $t->string('token');                // bot token
      $t->bigInteger('last_update_id')->nullable();
      $t->boolean('is_enabled')->default(true);
      $t->timestamps();
      $t->unique(['organization_id','token']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('telegram_bots');
  }
};
