<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->enum('type', ['text','pdf','url']);
            $table->string('title')->nullable();
            $table->text('text_content')->nullable();   // para "text"
            $table->string('storage_path')->nullable(); // para "pdf" subido
            $table->string('url')->nullable();          // para "url"
            $table->string('status', 32)->default('pending'); // pending|ready|error
            $table->json('meta')->nullable(); // hash, páginas, etc.
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('sources');
    }
};
