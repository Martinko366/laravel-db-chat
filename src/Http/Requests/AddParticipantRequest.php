<?php

namespace Martinko366\LaravelDbChat\Http\Requests;

use Illuminate\Validation\Rule;

class AddParticipantRequest extends BaseChatRequest
{
    protected function prepareForValidation(): void
    {
        $userId = $this->input('user_id');

        if (is_numeric($userId)) {
            $userId = (int) $userId;
        }

        $this->merge([
            'user_id' => $userId,
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists($this->userTable(), $this->userKeyName())],
        ];
    }
}
