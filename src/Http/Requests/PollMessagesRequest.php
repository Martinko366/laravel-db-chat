<?php

namespace Martinko366\LaravelDbChat\Http\Requests;

class PollMessagesRequest extends BaseChatRequest
{
    protected function prepareForValidation(): void
    {
        $afterMessageId = $this->input('after_message_id');

        if ($afterMessageId === null) {
            $afterMessageId = 0;
        }

        if (is_numeric($afterMessageId)) {
            $afterMessageId = (int) $afterMessageId;
        }

        $this->merge([
            'after_message_id' => $afterMessageId,
        ]);
    }

    public function rules(): array
    {
        return [
            'after_message_id' => ['required', 'integer', 'min:0'],
        ];
    }
}
