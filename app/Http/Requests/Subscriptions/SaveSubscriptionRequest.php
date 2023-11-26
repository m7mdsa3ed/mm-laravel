<?php

namespace App\Http\Requests\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;

class SaveSubscriptionRequest extends FormRequest
{
    /** Determine if the user is authorized to make this request. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'amount' => 'required|numeric',
            'interval_unit' => 'required|numeric',
            'interval_count' => 'required|numeric',
            'account_id' => 'required|numeric',
            'auto_renewal' => 'nullable|boolean',
            'can_cancel' => 'nullable|boolean',
            'started_at' => 'nullable|date',
        ];
    }
}
