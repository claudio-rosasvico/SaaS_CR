<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $t) {
            $t->string('public_key', 64)->nullable()->unique()->after('id');
            $t->boolean('is_embeddable')->default(true);
            $t->json('embed_theme')->nullable(); // colores, textos, posición, etc.
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (Blueprint $t) {
            $t->dropColumn(['public_key', 'is_embeddable', 'embed_theme']);
        });
    }
};
