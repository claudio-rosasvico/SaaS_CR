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
        // database/migrations/... add indexes
        Schema::table('sources', fn($t) => $t->index(['organization_id', 'created_at']));
        Schema::table('knowledge_chunks', fn($t) => $t->index(['organization_id', 'source_id']));
        Schema::table('conversations', fn($t) => $t->index(['organization_id', 'started_at']));
        Schema::table('messages', fn($t) => $t->index(['organization_id', 'conversation_id']));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
