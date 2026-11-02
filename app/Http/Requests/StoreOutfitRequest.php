<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOutfitRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:80'],
            'occasion' => ['required', 'string', Rule::in(array_keys(config('stylist.scoring.occasions')))],
            'season' => ['nullable', 'string', Rule::in(['spring', 'summer', 'autumn', 'winter'])],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'item_ids' => ['required', 'array', 'min:2'],
            'item_ids.*' => [
                'integer',
                Rule::exists('items', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            // Map of slot => item id, e.g. { top: 1, bottom: 2, shoes: 3 }
            'slots' => ['required', 'array'],
            'slots.*' => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $slots = $this->input('slots', []);

            if (! is_array($slots)) {
                return;
            }

            $allowed = ['top', 'bottom', 'dress', 'outer', 'shoes', 'accessory'];

            foreach (array_keys($slots) as $slot) {
                if (! in_array((string) $slot, $allowed, true)) {
                    $validator->errors()->add('slots', "Invalid slot [{$slot}].");
                }
            }
        });
    }
}
