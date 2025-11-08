<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('dbchat.tables.messages', 'chat_messages'), function (Blueprint $table) {
            $table->id(); // Monotonically increasing ID for cursor-based polling
            $table->foreignId('conversation_id')->constrained(config('dbchat.tables.conversations', 'chat_conversations'))->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->json('attachments')->nullable(); // Optional attachments metadata
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            // Critical index for efficient polling: WHERE conversation_id IN (...) AND id > ?
            $table->index(['conversation_id', 'id']);
            
            // Index for sender lookups
            $table->index('sender_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('dbchat.tables.messages', 'chat_messages'));
    }
};
