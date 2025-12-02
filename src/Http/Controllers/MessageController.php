<?php

namespace Martinko366\LaravelDbChat\Http\Controllers;

use Martinko366\LaravelDbChat\Services\MessageService;
use Martinko366\LaravelDbChat\Models\Conversation;
use Martinko366\LaravelDbChat\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Martinko366\LaravelDbChat\Http\Requests\ListMessagesRequest;
use Martinko366\LaravelDbChat\Http\Requests\StoreMessageRequest;
use Martinko366\LaravelDbChat\Http\Resources\MessageResource;

class MessageController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Get messages for a conversation.
     */
    public function index(ListMessagesRequest $request, Conversation $conversation): JsonResponse
    {
        // Check if user is a participant
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $validated = $request->validated();

        $messages = $this->messageService->getMessages(
            $conversation->id,
            $validated['before_message_id'] ?? null,
            $validated['limit']
        );

        return response()->json([
            'messages' => MessageResource::collection($messages),
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        // Check if user is a participant
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $validated = $request->validated();

        try {
            $message = $this->messageService->send(
                $conversation->id,
                $request->user()->id,
                $validated['body'],
                $validated['attachments'] ?? null
            );

            return response()->json([
                'message' => MessageResource::make($message),
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
