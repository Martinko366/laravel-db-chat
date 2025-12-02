<?php

namespace Martinko366\LaravelDbChat\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'participants' => ParticipantResource::collection($this->whenLoaded('participants')),
            'latest_message' => MessageResource::make($this->whenLoaded('latestMessage')),
        ];
    }
}
