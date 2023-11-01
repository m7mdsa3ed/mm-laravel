<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $userId
 * @property mixed $userName
 * @property mixed $userDisplayName
 * @property mixed $userVerification
 * @property mixed $clientDataJSON
 * @property mixed $attestationObject
 * @property mixed $challenge
 * @property mixed $id
 * @property mixed $userHandle
 * @property mixed $authenticatorData
 * @property mixed $signature
 */
class WebAuthApiRequest extends FormRequest
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

        ];
    }

    protected function prepareForValidation(): void
    {

    }
}
