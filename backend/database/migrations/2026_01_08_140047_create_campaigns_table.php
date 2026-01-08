<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();

            $table->string('name'); // internal name
            $table->string('status')->default('draft'); // draft|scheduled|sending|sent|paused

            $table->string('subject')->nullable();
            $table->string('preheader')->nullable();

            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to')->nullable();

            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();

            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
