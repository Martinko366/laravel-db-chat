<?php

namespace Martinko366\LaravelDbChat\Services;

use Martinko366\LaravelDbChat\Models\Message;
use Martinko366\LaravelDbChat\Models\MessageRead;
use InvalidArgumentException;

class MessageService
{
    /**
     * Send a message in a conversation.
     *
     * @param int $conversationId
     * @param int $senderId
     * @param string $body
     * @param array|null $attachments
     * @return Message
     * @throws InvalidArgumentException
     */
    public function send(int $conversationId, int $senderId, string $body, ?array $attachments = null): Message
    {
        $maxLength = config('dbchat.messages.max_length', 5000);
        
        if (strlen($body) > $maxLength) {
            throw new InvalidArgumentException("Message body cannot exceed {$maxLength} characters");
        }

        if (empty(trim($body))) {
            throw new InvalidArgumentException('Message body cannot be empty');
        }

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'body' => $body,
            'attachments' => $attachments,
        ]);

        return $message->load(['sender', 'reads']);
    }

    /**
     * Mark a message as read by a user.
     *
     * @param int $messageId
     * @param int $userId
     * @return MessageRead
     */
    public function markAsRead(int $messageId, int $userId): MessageRead
    {
        return MessageRead::firstOrCreate(
            [
                'message_id' => $messageId,
                'user_id' => $userId,
            ],
            [
                'read_at' => now(),
            ]
        );
    }

    /**
     * Get messages after a specific message ID for polling.
     *
     * @param array $conversationIds
     * @param int $afterMessageId
     * @param int|null $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNewMessages(array $conversationIds, int $afterMessageId, ?int $limit = null)
    {
        $query = Message::inConversations($conversationIds)
            ->after($afterMessageId)
            ->with(['sender', 'conversation', 'reads'])
            ->orderBy('id');

        if (!is_null($limit)) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get historical messages for a conversation (pagination).
     *
     * @param int $conversationId
     * @param int|null $beforeMessageId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMessages(int $conversationId, ?int $beforeMessageId = null, int $limit = 50)
    {
        $query = Message::where('conversation_id', $conversationId)
            ->with(['sender', 'reads']);

        if (!is_null($beforeMessageId)) {
            $query->before($beforeMessageId);
        }

        return $query->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Long polling for new messages.
     * Waits up to $timeout seconds for new messages.
     *
     * @param array $conversationIds
     * @param int $afterMessageId
     * @param int $timeout Maximum wait time in seconds
     * @param int $checkInterval How often to check in milliseconds
     * @return array ['last_message_id' => int, 'messages' => Collection]
     */
    public function poll(array $conversationIds, int $afterMessageId, int $timeout = 25, int $checkInterval = 500): array
    {
        $startTime = microtime(true);
        $timeoutMicroseconds = $timeout * 1000000;
        $checkIntervalMicroseconds = $checkInterval * 1000;

        while (true) {
            $messages = $this->getNewMessages($conversationIds, $afterMessageId);

            if ($messages->isNotEmpty()) {
                return [
                    'last_message_id' => $messages->last()->id,
                    'messages' => $messages,
                ];
            }

            // Check if timeout has been reached
            $elapsed = (microtime(true) - $startTime) * 1000000;
            if ($elapsed >= $timeoutMicroseconds) {
                return [
                    'last_message_id' => $afterMessageId,
                    'messages' => collect([]),
                ];
            }

            // Sleep before checking again
            usleep($checkIntervalMicroseconds);
        }
    }

    /**
     * Get the highest message ID across user's conversations.
     *
     * @param array $conversationIds
     * @return int
     */
    public function getLastMessageId(array $conversationIds): int
    {
        if (empty($conversationIds)) {
            return 0;
        }

        return Message::inConversations($conversationIds)
            ->max('id') ?? 0;
    }
}
