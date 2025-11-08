<?php

namespace Martinko366\LaravelDbChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('dbchat.tables.messages', 'chat_messages');
    }

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the sender of this message.
     */
    public function sender(): BelongsTo
    {
        $userModel = config('dbchat.user_model', 'App\Models\User');
        return $this->belongsTo($userModel, 'sender_id');
    }

    /**
     * Get all read receipts for this message.
     */
    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class, 'message_id');
    }

    /**
     * Check if this message has been read by a specific user.
     */
    public function isReadBy(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    /**
     * Scope to get messages after a specific message ID.
     */
    public function scopeAfter($query, int $messageId)
    {
        return $query->where('id', '>', $messageId);
    }

    /**
     * Scope to get messages before a specific message ID (for pagination).
     */
    public function scopeBefore($query, int $messageId)
    {
        return $query->where('id', '<', $messageId);
    }

    /**
     * Scope to filter messages by conversation IDs.
     */
    public function scopeInConversations($query, array $conversationIds)
    {
        return $query->whereIn('conversation_id', $conversationIds);
    }
}
