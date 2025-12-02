<?php

namespace Martinko366\LaravelDbChat\Http\Controllers;

use Martinko366\LaravelDbChat\Services\ConversationService;
use Martinko366\LaravelDbChat\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Martinko366\LaravelDbChat\Http\Requests\StoreConversationRequest;
use Martinko366\LaravelDbChat\Http\Requests\AddParticipantRequest;
use Martinko366\LaravelDbChat\Http\Resources\ConversationResource;
use Martinko366\LaravelDbChat\Http\Resources\ParticipantResource;

class ConversationController extends Controller
{
    public function __construct(
        protected ConversationService $conversationService
    ) {}

    /**
     * Get all conversations for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $conversations = $this->conversationService->getUserConversations(
            $request->user()->id
        );

        return response()->json([
            'conversations' => ConversationResource::collection($conversations),
        ]);
    }

    /**
     * Create a new conversation.
     */
    public function store(StoreConversationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $conversation = $this->conversationService->create(
                $validated['type'],
                $validated['participants'],
                $request->user()->id,
                $validated['title'] ?? null
            );

            return response()->json([
                'conversation' => ConversationResource::make($conversation),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get a specific conversation.
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        // Check if user is a participant
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $conversation->load(['participants.user', 'latestMessage.sender']);

        return response()->json([
            'conversation' => ConversationResource::make($conversation),
        ]);
    }

    /**
     * Add a participant to a group conversation.
     */
    public function addParticipant(AddParticipantRequest $request, Conversation $conversation): JsonResponse
    {
        // Check if user is a participant
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $validated = $request->validated();

        try {
            $participant = $this->conversationService->addParticipant(
                $conversation,
                $validated['user_id']
            );

            return response()->json([
                'participant' => ParticipantResource::make($participant),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove a participant from a group conversation.
     */
    public function removeParticipant(Request $request, Conversation $conversation, int $userId): JsonResponse
    {
        // Check if user is a participant
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        try {
            $this->conversationService->removeParticipant($conversation, $userId);

            return response()->json(null, 204);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
