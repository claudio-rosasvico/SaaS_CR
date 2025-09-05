<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->nullable();
            $t->foreignId('conversation_id')->nullable();
            $t->string('provider', 20)->nullable();      // ollama|openai|gemini
            $t->string('model', 80)->nullable();
            $t->integer('duration_ms')->nullable();
            $t->integer('tokens_in')->nullable();
            $t->integer('tokens_out')->nullable();
            $t->timestamps();
        });

        Schema::table('analytics_events', function (Blueprint $t) {
            $t->index(['organization_id', 'created_at']);
            $t->index(['provider', 'created_at']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
