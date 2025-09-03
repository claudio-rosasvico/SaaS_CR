<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index(); // por si luego sumamos multi-tenant
            $table->string('channel', 32)->default('web'); // web|whatsapp|telegram|...
            $table->string('external_id')->nullable(); // ej: wa_id o thread_id del canal
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('conversations');
    }
};
