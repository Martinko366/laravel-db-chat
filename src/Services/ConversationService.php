<?php

namespace Martinko366\LaravelDbChat\Services;

use Martinko366\LaravelDbChat\Models\Conversation;
use Martinko366\LaravelDbChat\Models\Participant;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ConversationService
{
    /**
     * Create a new conversation.
     *
     * @param string $type 'direct' or 'group'
     * @param array $participantIds Array of user IDs
     * @param int $creatorId The user creating the conversation
     * @param string|null $title Optional title for group chats
     * @return Conversation
     * @throws InvalidArgumentException
     */
    public function create(string $type, array $participantIds, int $creatorId, ?string $title = null): Conversation
    {
        // Validate type
        if (!in_array($type, ['direct', 'group'])) {
            throw new InvalidArgumentException('Type must be either "direct" or "group"');
        }

        // Ensure creator is included in participants
        if (!in_array($creatorId, $participantIds)) {
            $participantIds[] = $creatorId;
        }

        // Validate participant count
        if ($type === 'direct' && count($participantIds) !== 2) {
            throw new InvalidArgumentException('Direct conversations must have exactly 2 participants');
        }

        if ($type === 'group' && count($participantIds) < 2) {
            throw new InvalidArgumentException('Group conversations must have at least 2 participants');
        }

        return DB::transaction(function () use ($type, $participantIds, $title) {
            // For direct chats, check if conversation already exists
            if ($type === 'direct') {
                $existing = $this->findDirectConversation($participantIds);
                if ($existing) {
                    return $existing->load(['participants.user', 'latestMessage.sender']);
                }
            }

            // Create conversation
            $conversation = Conversation::create([
                'type' => $type,
                'title' => $title,
            ]);

            // Add participants
            foreach ($participantIds as $userId) {
                Participant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'joined_at' => now(),
                ]);
            }

            return $conversation->load(['participants.user', 'latestMessage.sender']);
        });
    }

    /**
     * Find an existing direct conversation between two users.
     *
     * @param array $userIds Array of exactly 2 user IDs
     * @return Conversation|null
     */
    protected function findDirectConversation(array $userIds): ?Conversation
    {
        if (count($userIds) !== 2) {
            return null;
        }

        sort($userIds); // Normalize order

        return Conversation::where('type', 'direct')
            ->whereHas('participants', function ($q) use ($userIds) {
                $q->where('user_id', $userIds[0]);
            })
            ->whereHas('participants', function ($q) use ($userIds) {
                $q->where('user_id', $userIds[1]);
            })
            ->whereDoesntHave('participants', function ($q) use ($userIds) {
                $q->whereNotIn('user_id', $userIds);
            })
            ->first();
    }

    /**
     * Add a participant to an existing conversation.
     *
     * @param Conversation $conversation
     * @param int $userId
     * @return Participant
     * @throws InvalidArgumentException
     */
    public function addParticipant(Conversation $conversation, int $userId): Participant
    {
        // Can't add participants to direct conversations
        if ($conversation->isDirect()) {
            throw new InvalidArgumentException('Cannot add participants to direct conversations');
        }

        // Check if already a participant
        if ($conversation->hasParticipant($userId)) {
            throw new InvalidArgumentException('User is already a participant');
        }

        $participant = Participant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'joined_at' => now(),
        ]);

        return $participant->load('user');
    }

    /**
     * Remove a participant from a conversation.
     *
     * @param Conversation $conversation
     * @param int $userId
     * @return bool
     * @throws InvalidArgumentException
     */
    public function removeParticipant(Conversation $conversation, int $userId): bool
    {
        // Can't remove participants from direct conversations
        if ($conversation->isDirect()) {
            throw new InvalidArgumentException('Cannot remove participants from direct conversations');
        }

        return $conversation->participants()
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Get all conversations for a user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserConversations(int $userId)
    {
        return Conversation::forUser($userId)
            ->with(['latestMessage.sender', 'participants.user'])
            ->orderByDesc(function ($query) {
                $query->select('created_at')
                    ->from(config('dbchat.tables.messages', 'chat_messages'))
                    ->whereColumn('conversation_id', config('dbchat.tables.conversations', 'chat_conversations') . '.id')
                    ->latest('id')
                    ->limit(1);
            })
            ->get();
    }
}
