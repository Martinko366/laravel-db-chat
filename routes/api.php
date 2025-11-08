<?php

use Illuminate\Support\Facades\Route;
use Martinko366\LaravelDbChat\Http\Controllers\ConversationController;
use Martinko366\LaravelDbChat\Http\Controllers\MessageController;
use Martinko366\LaravelDbChat\Http\Controllers\PollController;

/*
|--------------------------------------------------------------------------
| DB Chat API Routes
|--------------------------------------------------------------------------
|
| These routes handle all chat-related API endpoints including conversations,
| messages, polling, and read receipts.
|
*/

Route::middleware(config('dbchat.route.middleware', ['api', 'auth:sanctum']))
    ->prefix(config('dbchat.route.prefix', 'api/dbchat'))
    ->group(function () {
        // Conversations
        Route::get('/conversations', [ConversationController::class, 'index'])
            ->name('dbchat.conversations.index');

        Route::post('/conversations', [ConversationController::class, 'store'])
            ->name('dbchat.conversations.store')
            ->middleware('throttle:' . config('dbchat.messages.rate_limit', 60));

        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])
            ->name('dbchat.conversations.show');

        Route::post('/conversations/{conversation}/participants', [ConversationController::class, 'addParticipant'])
            ->name('dbchat.conversations.participants.add');

        Route::delete('/conversations/{conversation}/participants/{userId}', [ConversationController::class, 'removeParticipant'])
            ->name('dbchat.conversations.participants.remove');

        // Messages
        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])
            ->name('dbchat.messages.index');

        Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])
            ->name('dbchat.messages.store')
            ->middleware('throttle:' . config('dbchat.messages.rate_limit', 60));

        // Read receipts
        Route::post('/messages/{message}/read', [MessageController::class, 'markAsRead'])
            ->name('dbchat.messages.read');

        // Long polling
        Route::get('/poll', PollController::class)
            ->name('dbchat.poll')
            ->middleware('throttle:' . config('dbchat.polling.rate_limit', 120));
    });
