<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedInteger('position')->default(0); // orden del chunk
            $table->text('content');                         // el fragmento
            $table->json('embedding')->nullable();           // guardamos como JSON por ahora (más adelante vectores)
            $table->json('metadata')->nullable();            // página, url, título, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_id', 'position']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('knowledge_chunks');
    }
};
