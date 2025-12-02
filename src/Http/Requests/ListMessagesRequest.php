<?php

namespace Martinko366\LaravelDbChat\Http\Requests;

class ListMessagesRequest extends BaseChatRequest
{
    protected function prepareForValidation(): void
    {
        $limit = $this->input('limit');
        if ($limit === null) {
            $limit = config('dbchat.messages.pagination_limit', 50);
        }

        if (is_numeric($limit)) {
            $limit = (int) $limit;
        }

        $this->merge([
            'limit' => $limit,
        ]);
    }

    public function rules(): array
    {
        return [
            'before_message_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
