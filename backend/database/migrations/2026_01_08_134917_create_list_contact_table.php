<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('list_contact', function (Blueprint $table) {
            $table->foreignId('mailing_list_id')->constrained('mailing_lists')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->primary(['mailing_list_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_contact');
    }
};
