<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePomodoroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('user_id', $this->user()->id)],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:180'],
        ];
    }
}
