<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('session')->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['sometimes', 'required', Rule::exists('subjects', 'id')->where('user_id', $this->user()->id)],
            'scheduled_date' => ['sometimes', 'required', 'date'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i', 'after:start_time'],
            'status' => ['sometimes', 'required', Rule::in(['scheduled', 'in_progress', 'completed', 'missed', 'rescheduled'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
