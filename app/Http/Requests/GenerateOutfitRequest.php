<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateOutfitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'occasion' => ['required', 'string', Rule::in(array_keys(config('stylist.scoring.occasions')))],
            'season' => ['nullable', 'string', Rule::in(['spring', 'summer', 'autumn', 'winter'])],
            'temperature' => ['required', 'string', Rule::in(array_keys(config('stylist.scoring.temperatures')))],
            'count' => [
                'nullable',
                'integer',
                'min:1',
                'max:'.(int) config('stylist.scoring.max_count', 10),
            ],
        ];
    }
}
