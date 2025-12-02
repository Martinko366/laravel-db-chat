<?php

namespace Martinko366\LaravelDbChat\Http\Controllers;

use Martinko366\LaravelDbChat\Services\MessageService;
use Martinko366\LaravelDbChat\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Martinko366\LaravelDbChat\Http\Requests\PollMessagesRequest;
use Martinko366\LaravelDbChat\Http\Resources\MessageResource;

class PollController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    /**
     * Long polling endpoint for realtime message updates.
     */
    public function __invoke(PollMessagesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Get all conversation IDs where user is a participant
        $conversationIds = Participant::where('user_id', $request->user()->id)
            ->pluck('conversation_id')
            ->toArray();

        if (empty($conversationIds)) {
            return response()->json([
                'last_message_id' => $validated['after_message_id'],
                'messages' => [],
            ]);
        }

        $timeout = config('dbchat.polling.timeout', 25);
        $checkInterval = config('dbchat.polling.check_interval', 500);

        $result = $this->messageService->poll(
            $conversationIds,
            $validated['after_message_id'],
            $timeout,
            $checkInterval
        );

        // If no new messages, return 204 No Content
        if ($result['messages']->isEmpty()) {
            return response()->json(null, 204);
        }

        return response()->json([
            'last_message_id' => $result['last_message_id'],
            'messages' => MessageResource::collection($result['messages']),
        ]);
    }
}
