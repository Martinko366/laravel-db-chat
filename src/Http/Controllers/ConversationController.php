<?php

namespace Martinko366\LaravelDbChat\Http\Controllers;

use Martinko366\LaravelDbChat\Services\ConversationService;
use Martinko366\LaravelDbChat\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

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
            'conversations' => $conversations,
        ]);
    }

    /**
     * Create a new conversation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['direct', 'group'])],
            'participants' => 'required|array|min:1',
            'participants.*' => 'required|integer',
            'title' => 'nullable|string|max:255',
        ]);

        try {
            $conversation = $this->conversationService->create(
                $validated['type'],
                $validated['participants'],
                $request->user()->id,
                $validated['title'] ?? null
            );

            return response()->json([
                'conversation' => $conversation,
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

        $conversation->load(['participants.user', 'latestMessage']);

        return response()->json([
            'conversation' => $conversation,
        ]);
    }

    /**
     * Add a participant to a group conversation.
     */
    public function addParticipant(Request $request, Conversation $conversation): JsonResponse
    {
        // Check if user is a participant
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer',
        ]);

        try {
            $participant = $this->conversationService->addParticipant(
                $conversation,
                $validated['user_id']
            );

            return response()->json([
                'participant' => $participant->load('user'),
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
