<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('dbchat.tables.message_reads', 'chat_message_reads'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained(config('dbchat.tables.messages', 'chat_messages'))->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->useCurrent();

            // Ensure a user can only mark a message as read once
            $table->unique(['message_id', 'user_id']);
            
            // Index for fetching read receipts per message
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('dbchat.tables.message_reads', 'chat_message_reads'));
    }
};
