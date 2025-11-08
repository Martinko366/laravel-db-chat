<?php

namespace Martinko366\LaravelDbChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageRead extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('dbchat.tables.message_reads', 'chat_message_reads');
    }

    /**
     * Get the message this read receipt belongs to.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who read the message.
     */
    public function user(): BelongsTo
    {
        $userModel = config('dbchat.user_model', 'App\Models\User');
        return $this->belongsTo($userModel, 'user_id');
    }
}
