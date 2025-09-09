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
        Schema::create('channel_integrations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->index();
            $t->string('channel'); // 'telegram'
            $t->boolean('enabled')->default(true);
            $t->json('config');    // { "token": "123:ABC", "last_update_id": 12345 }
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_integrations');
    }
};
