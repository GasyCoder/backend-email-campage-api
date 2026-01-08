<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_lists', function (Blueprint $table) {
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mailing_list_id')->constrained('mailing_lists')->cascadeOnDelete();
            $table->primary(['campaign_id', 'mailing_list_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_lists');
    }
};
