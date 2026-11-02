<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWearLogRequest extends FormRequest
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
            'outfit_id' => [
                'nullable',
                'integer',
                Rule::exists('outfits', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'item_ids' => ['nullable', 'array', 'min:1'],
            'item_ids.*' => [
                'integer',
                Rule::exists('items', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'worn_on' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('outfit_id') && empty($this->input('item_ids'))) {
                $validator->errors()->add('outfit_id', 'Choose a saved outfit or at least one wardrobe item.');
            }
        });
    }
}
