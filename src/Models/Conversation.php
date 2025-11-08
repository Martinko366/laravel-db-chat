<?php

namespace Martinko366\LaravelDbChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'title',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('dbchat.tables.conversations', 'chat_conversations');
    }

    /**
     * Get all participants in this conversation.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Get all users in this conversation.
     */
    public function users(): BelongsToMany
    {
        $userModel = config('dbchat.user_model', 'App\Models\User');
        
        return $this->belongsToMany(
            $userModel,
            config('dbchat.tables.participants', 'chat_participants'),
            'conversation_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Get all messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    /**
     * Get the latest message in this conversation.
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany('id');
    }

    /**
     * Check if this is a direct conversation.
     */
    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    /**
     * Check if this is a group conversation.
     */
    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /**
     * Check if a user is a participant in this conversation.
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    /**
     * Scope to filter conversations by participant.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }
}
