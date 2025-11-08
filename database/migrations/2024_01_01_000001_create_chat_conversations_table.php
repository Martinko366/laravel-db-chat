<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('dbchat.tables.conversations', 'chat_conversations'), function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['direct', 'group'])->index();
            $table->string('title')->nullable(); // For group chats
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('dbchat.tables.conversations', 'chat_conversations'));
    }
};
