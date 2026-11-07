<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('assignment')->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['sometimes', 'required', Rule::exists('subjects', 'id')->where('user_id', $this->user()->id)],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'required', Rule::in(['assignment', 'exam'])],
            'due_date' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'completed'])],
        ];
    }
}
