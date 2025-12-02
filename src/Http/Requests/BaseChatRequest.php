<?php

namespace Martinko366\LaravelDbChat\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseChatRequest extends FormRequest
{
    private ?Model $userModelInstance = null;

    public function authorize(): bool
    {
        return true;
    }

    protected function resolveUserModel(): Model
    {
        if ($this->userModelInstance === null) {
            $modelClass = config('dbchat.user_model', 'App\\Models\\User');
            $this->userModelInstance = app($modelClass);
        }

        return $this->userModelInstance;
    }

    protected function userTable(): string
    {
        return $this->resolveUserModel()->getTable();
    }

    protected function userKeyName(): string
    {
        return $this->resolveUserModel()->getKeyName();
    }
}
