<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tracking_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->string('hash', 64);
            $table->text('url');
            $table->timestamps();

            $table->unique(['message_id', 'hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_links');
    }
};
