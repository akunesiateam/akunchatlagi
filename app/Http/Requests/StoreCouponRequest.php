<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Handle authorization in middleware or gates
    }

    public function rules()
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('coupons', 'code'),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->type === 'percentage' && $value > 100) {
                        $fail('Percentage discount cannot exceed 100%.');
                    }
                },
            ],
            'usage_limit' => 'nullable|integer|min:1|max:1000000',
            'usage_limit_per_customer' => 'nullable|integer|min:1|max:100',
            'starts_at' => 'nullable|date|after_or_equal:today',
            'expires_at' => 'nullable|date|after:starts_at',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->type === 'fixed_amount' && $value && $value < $this->value) {
                        $fail('Maximum discount cannot be less than the coupon value for fixed amount coupons.');
                    }
                },
            ],
            'applicable_plans' => 'nullable|array',
            'applicable_plans.*' => 'exists:plans,id',
            'applicable_billing_periods' => 'nullable|array',
            'applicable_billing_periods.*' => 'in:monthly,yearly',
            'first_payment_only' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'code.alpha_dash' => 'Coupon code may only contain letters, numbers, dashes and underscores.',
            'code.unique' => 'This coupon code is already taken.',
            'expires_at.after' => 'Expiry date must be after the start date.',
            'starts_at.after_or_equal' => 'Start date cannot be in the past.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'code' => strtoupper($this->code ?? ''),
        ]);
    }
}
