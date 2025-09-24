<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email');
            $table->string('empresa')->nullable();
            $table->string('servicio'); // chatbots | backoffice
            $table->text('mensaje')->nullable();
            $table->string('status')->default('nuevo'); // nuevo | respondido
            $table->timestamp('responded_at')->nullable();
            $table->string('response_subject')->nullable();
            $table->longText('response_body')->nullable();
            $table->string('responded_by')->nullable();

            // metadatos útiles
            $table->string('ip', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->json('meta')->nullable();

            // honeypot (anti-spam)
            $table->string('website')->nullable();

            $table->timestamps();
            $table->index(['email', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('contact_messages');
    }
};
