<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMeasurementRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'measured_on' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
            'height_cm' => ['required', 'numeric', 'between:50,250'],
            'weight_kg' => ['nullable', 'numeric', 'between:20,400'],
            'neck_cm' => ['nullable', 'numeric', 'between:20,80'],
            'shoulder_width_cm' => ['nullable', 'numeric', 'between:20,80'],
            'chest_cm' => ['required', 'numeric', 'between:30,250'],
            'waist_cm' => ['required', 'numeric', 'between:30,250'],
            'hips_cm' => ['required', 'numeric', 'between:30,250'],
            'inseam_cm' => ['nullable', 'numeric', 'between:30,120'],
            'arm_length_cm' => ['nullable', 'numeric', 'between:30,100'],
            'thigh_cm' => ['nullable', 'numeric', 'between:20,120'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $waist = $this->input('waist_cm');
            $hips = $this->input('hips_cm');

            if (! is_numeric($waist) || ! is_numeric($hips) || (float) $hips <= 0) {
                return;
            }

            // A waist more than 1.5x the hips is almost certainly a typo.
            if ((float) $waist > (float) $hips * 1.5) {
                $validator->errors()->add(
                    'waist_cm',
                    'The waist cannot be more than 1.5 times the hips.'
                );
            }
        });
    }
}
