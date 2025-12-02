<?php

namespace Martinko366\LaravelDbChat\Http\Requests;

use Illuminate\Validation\Rule;

class StoreConversationRequest extends BaseChatRequest
{
    protected function prepareForValidation(): void
    {
        $participants = $this->input('participants');

        if (is_string($participants)) {
            $participants = array_filter(array_map('trim', explode(',', $participants)));
        }

        if (is_array($participants)) {
            $participants = array_values(array_unique(array_map(static function ($id) {
                return is_numeric($id) ? (int) $id : $id;
            }, $participants)));
        }

        $title = $this->input('title');
        if (is_string($title)) {
            $title = trim($title);
        }

        $this->merge([
            'participants' => $participants,
            'title' => $title,
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['direct', 'group'])],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*' => ['required', 'integer', 'distinct', Rule::exists($this->userTable(), $this->userKeyName())],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
