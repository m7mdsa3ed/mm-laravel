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
        $user = auth()->user();

        $this->merge([
            'apple' => '0',
            'yubico' => '0',
            'solo' => '0',
            'hypersecu' => '0',
            'google' => '0',
            'microsoft' => '0',
            'mds' => '1',
            'requireResidentKey' => '0',
            'type_usb' => '1',
            'type_nfc' => '1',
            'type_ble' => '1',
            'type_int' => '1',
            'type_hybrid' => '1',
            'fmt_android-key' => '1',
            'fmt_android-safetynet' => '1',
            'fmt_apple' => '1',
            'fmt_fido-u2f' => '1',
            'fmt_none' => '1',
            'fmt_packed' => '1',
            'fmt_tpm' => '1',
            'rpId' => $this->getHost(),
            'userId' => $user->id,
            'userName' => $user->name,
            'userDisplayName' => $user->name,
            'userVerification' => 'discouraged',
        ]);

        $userId = preg_replace('/[^0-9a-f]/i', '', $this->userId);

        $userName = preg_replace('/[^0-9a-z]/i', '', $this->userName);

        $userDisplayName = preg_replace('/[^0-9a-z öüäéèàÖÜÄÉÈÀÂÊÎÔÛâêîôû]/i', '', $this->userDisplayName);

        $clientDataJSON = $this->clientDataJSON ? base64_decode($this->clientDataJSON) : null;

        $attestationObject = $this->attestationObject ? base64_decode($this->attestationObject) : null;

        $authenticatorData = $this->authenticatorData ? base64_decode($this->authenticatorData) : null;

        $signature = $this->signature ? base64_decode($this->signature) : null;

        $userHandle = $this->userHandle ? base64_decode($this->userHandle) : null;

        $id = $this->id ? base64_decode($this->id) : null;

        $this->merge([
            'userId' => bin2hex($userId),
            'userName' => $userName,
            'userDisplayName' => $userDisplayName,
            'clientDataJSON' => $clientDataJSON,
            'attestationObject' => $attestationObject,
            'authenticatorData' => $authenticatorData,
            'signature' => $signature,
            'userHandle' => $userHandle,
            'id' => $id,
        ]);
    }
}
