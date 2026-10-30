<?php

namespace App\Http\Requests;

use App\Enums\ItemCategory;
use App\Enums\ItemFitType;
use App\Enums\ItemPattern;
use App\Enums\ItemStretch;
use App\Enums\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::enum(ItemCategory::class)],
            'subcategory' => ['required', 'string', 'max:60'],
            'primary_color_hex' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color_hex' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'pattern' => ['required', Rule::enum(ItemPattern::class)],
            'material' => ['nullable', 'string', 'max:100'],
            'seasons' => ['required', 'array', 'min:1', 'max:4'],
            'seasons.*' => ['string', Rule::enum(Season::class)],
            'formality' => ['required', 'integer', 'between:1,5'],
            'fit_type' => ['required', Rule::enum(ItemFitType::class)],
            'stretch' => ['required', Rule::enum(ItemStretch::class)],
            'cut_tags' => ['nullable', 'array', 'max:10'],
            'cut_tags.*' => ['string', Rule::in(config('stylist.cut_tags'))],
            'size_label' => ['nullable', 'string', 'max:20'],
            'g_chest_width' => ['nullable', 'numeric', 'between:0.5,300'],
            'g_waist_width' => ['nullable', 'numeric', 'between:0.5,300'],
            'g_hip_width' => ['nullable', 'numeric', 'between:0.5,300'],
            'g_length' => ['nullable', 'numeric', 'between:0.5,300'],
            'g_inseam' => ['nullable', 'numeric', 'between:0.5,300'],
            'g_shoulder_width' => ['nullable', 'numeric', 'between:0.5,300'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
