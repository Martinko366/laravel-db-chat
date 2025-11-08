<?php

namespace Martinko366\LaravelDbChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('dbchat.tables.participants', 'chat_participants');
    }

    /**
     * Get the conversation this participant belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user for this participant.
     */
    public function user(): BelongsTo
    {
        $userModel = config('dbchat.user_model', 'App\Models\User');
        return $this->belongsTo($userModel, 'user_id');
    }
}
