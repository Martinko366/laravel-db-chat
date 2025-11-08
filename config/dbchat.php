<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Customize the table names used by the chat system.
    |
    */
    'tables' => [
        'conversations' => 'chat_conversations',
        'participants' => 'chat_participants',
        'messages' => 'chat_messages',
        'message_reads' => 'chat_message_reads',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model used for chat participants.
    |
    */
    'user_model' => env('DBCHAT_USER_MODEL', 'App\Models\User'),

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the route prefix and middleware for chat API endpoints.
    |
    */
    'route' => [
        'prefix' => 'api/dbchat',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Long Polling Configuration
    |--------------------------------------------------------------------------
    |
    | Configure long polling behavior for realtime updates.
    |
    */
    'polling' => [
        // Maximum time to wait for new messages (seconds)
        'timeout' => env('DBCHAT_POLL_TIMEOUT', 25),
        
        // How often to check for new messages (milliseconds)
        'check_interval' => env('DBCHAT_POLL_CHECK_INTERVAL', 500),
        
        // Rate limit for polling endpoint (requests per minute)
        'rate_limit' => env('DBCHAT_POLL_RATE_LIMIT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Configuration
    |--------------------------------------------------------------------------
    */
    'messages' => [
        // Maximum message body length
        'max_length' => env('DBCHAT_MESSAGE_MAX_LENGTH', 5000),
        
        // Pagination limit for historical messages
        'pagination_limit' => env('DBCHAT_MESSAGE_PAGINATION_LIMIT', 50),
        
        // Rate limit for sending messages (requests per minute)
        'rate_limit' => env('DBCHAT_MESSAGE_RATE_LIMIT', 60),
    ],
];
