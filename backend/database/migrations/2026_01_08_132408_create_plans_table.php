<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // free, pro
            $table->string('name');
            $table->unsignedInteger('monthly_credits')->default(0);
            $table->unsignedInteger('max_recipients_per_campaign')->default(0);
            $table->unsignedInteger('monthly_recipient_limit')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
