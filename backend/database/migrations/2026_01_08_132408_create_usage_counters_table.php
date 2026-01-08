<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7); // YYYY-MM
            $table->unsignedInteger('credits_used')->default(0);
            $table->unsignedInteger('recipients_sent')->default(0);
            $table->timestamps();

            $table->unique(['workspace_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
    }
};
