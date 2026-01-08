<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete(); // null = global
            $table->string('name');
            $table->string('category')->nullable(); // newsletter, promo, announcement...
            $table->longText('html'); // body html with placeholders
            $table->timestamps();

            $table->index(['workspace_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
