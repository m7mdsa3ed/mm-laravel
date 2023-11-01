<?php

namespace App\Services\Passkeys\DTOs;

use Illuminate\Http\Request;

class WebAuthApiDto
{
    public function __construct(
        public mixed $rpId,
        public mixed $userId,
        public mixed $userName,
        public mixed $userDisplayName,
        public mixed $apple = 0,
        public mixed $yubico = 0,
        public mixed $solo = 0,
        public mixed $hypersecu = 0,
        public mixed $google = 0,
        public mixed $microsoft = 0,
        public mixed $mds = 1,
        public mixed $requireResidentKey = 0,
        public mixed $type_usb = 1,
        public mixed $type_nfc = 1,
        public mixed $type_ble = 1,
        public mixed $type_int = 1,
        public mixed $type_hybrid = 1,
        public mixed $fmt_android = 1,
        public mixed $fmt_apple = 1,
        public mixed $fmt_fido = 1,
        public mixed $fmt_none = 1,
        public mixed $fmt_packed = 1,
        public mixed $fmt_tpm = 1,
        public mixed $userVerification = 'discouraged',
        public mixed $clientDataJSON = null,
        public mixed $attestationObject = null,
        public mixed $challenge = null,
        public mixed $authenticatorData = null,
        public mixed $signature = null,
        public mixed $userHandle = null,
        public mixed $id = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $userId = preg_replace('/[^0-9a-f]/i', '', $request->user()->id);

        $userName = preg_replace('/[^0-9a-z]/i', '', $request->user()->name);

        $userDisplayName = preg_replace('/[^0-9a-z öüäéèàÖÜÄÉÈÀÂÊÎÔÛâêîôû]/i', '', $request->user()->name);

        $clientDataJSON = $request->clientDataJSON ? base64_decode($request->clientDataJSON) : null;

        $attestationObject = $request->attestationObject ? base64_decode($request->attestationObject) : null;

        $authenticatorData = $request->authenticatorData ? base64_decode($request->authenticatorData) : null;

        $signature = $request->signature ? base64_decode($request->signature) : null;

        $userHandle = $request->userHandle ? base64_decode($request->userHandle) : null;

        $id = $request->id ? base64_decode($request->id) : null;

        return new self(
            rpId: self::getDomain($request),
            userId: bin2hex($userId),
            userName: $userName,
            userDisplayName: $userDisplayName,
            clientDataJSON: $clientDataJSON,
            attestationObject: $attestationObject,
            challenge: $request->challenge,
            authenticatorData: $authenticatorData,
            signature: $signature,
            userHandle: $userHandle,
            id: $id,
        );
    }

    private static function getDomain(Request $request)
    {
        $referer = $request->headers->get('referer');

        if ($referer) {
            $parsedUrl = parse_url($referer);

            if (isset($parsedUrl['host'])) {
                return $parsedUrl['host'];
            }
        }

        return $request->getHost();
    }
}
