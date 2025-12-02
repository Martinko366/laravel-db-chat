<?php

namespace Martinko366\LaravelDbChat\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageReadResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'message_id' => $this->message_id,
            'user_id' => $this->user_id,
            'read_at' => $this->read_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => $this->user),
        ];
    }
}
