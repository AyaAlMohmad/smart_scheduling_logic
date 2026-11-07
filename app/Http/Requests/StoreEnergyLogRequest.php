<?php

namespace App\Http\Requests;

use App\Models\EnergyLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnergyLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'energy_level' => ['required', Rule::in(EnergyLog::LEVELS)],
        ];
    }
}
