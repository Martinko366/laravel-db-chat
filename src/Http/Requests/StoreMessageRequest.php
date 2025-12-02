<?php

namespace Martinko366\LaravelDbChat\Http\Requests;

class StoreMessageRequest extends BaseChatRequest
{
    protected function prepareForValidation(): void
    {
        $attachments = $this->input('attachments');

        if ($attachments === null) {
            $attachments = [];
        }

        $this->merge([
            'attachments' => $attachments,
        ]);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['array'],
        ];
    }
}
