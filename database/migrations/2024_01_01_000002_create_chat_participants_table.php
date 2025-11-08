<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('dbchat.tables.participants', 'chat_participants'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained(config('dbchat.tables.conversations', 'chat_conversations'))->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            // Ensure a user can only be added once to a conversation
            $table->unique(['conversation_id', 'user_id']);
            
            // Index for fast lookups
            $table->index(['user_id', 'conversation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('dbchat.tables.participants', 'chat_participants'));
    }
};
