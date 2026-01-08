<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_send_id')->nullable()->constrained('campaign_sends')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();

            $table->string('to_email', 190);
            $table->string('subject')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to')->nullable();

            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();

            $table->string('status')->default('queued'); // queued|sent|failed|delivered|bounced|complained|unsubscribed
            $table->string('provider')->nullable(); // mailgun|log
            $table->string('provider_message_id')->nullable();
            $table->text('last_error')->nullable();

            $table->string('unsubscribe_signature')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            $table->index(['workspace_id', 'campaign_id', 'status']);
            $table->index(['provider', 'provider_message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
