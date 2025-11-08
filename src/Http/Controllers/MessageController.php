<?php

namespace Martinko366\LaravelDbChat\Http\Controllers;

use Martinko366\LaravelDbChat\Services\MessageService;
use Martinko366\LaravelDbChat\Models\Conversation;
use Martinko366\LaravelDbChat\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class MessageController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Get messages for a conversation.
     */
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        // Check if user is a participant
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $validated = $request->validate([
            'before_message_id' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $messages = $this->messageService->getMessages(
            $conversation->id,
            $validated['before_message_id'] ?? null,
            $validated['limit'] ?? config('dbchat.messages.pagination_limit', 50)
        );

        return response()->json([
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        // Check if user is a participant
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $validated = $request->validate([
            'body' => 'required|string',
            'attachments' => 'nullable|array',
        ]);

        try {
            $message = $this->messageService->send(
                $conversation->id,
                $request->user()->id,
                $validated['body'],
                $validated['attachments'] ?? null
            );

            return response()->json([
                'message' => $message,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(Request $request, Message $message): JsonResponse
    {
        // Check if user is a participant of the conversation
        if (!$message->conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $this->messageService->markAsRead(
            $message->id,
            $request->user()->id
        );

        return response()->json(null, 204);
    }
}
