<?php

namespace App\Services\Passkeys;

use App\Http\Requests\WebAuthApiRequest;
use App\Models\PassKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;
use Mockery\Exception;

class PasskeysService
{
    /** @throws WebAuthnException */
    public function createArgumentsForNewKey(WebAuthApiRequest $request): array
    {
        $webAuthn = $this->createWebAuthn($request);

        $createArgs = $webAuthn->getCreateArgs(
            \hex2bin($request->userId),
            $request->userName,
            $request->userDisplayName,
            60 * 4,
            $request->boolean('requireResidentKey'),
            $request->userVerification,
            $this->checkCrossPlatformAttachment($request)
        );

        return [
            'arguments' => $createArgs,
            'challenge' => $this->encodeChallenge($webAuthn->getChallenge()),
        ];
    }

    /** @throws WebAuthnException */
    public function getArgumentsForValidation(WebAuthApiRequest $request): array
    {
        $webAuthn = $this->createWebAuthn($request);

        $userId = $request->userId;

        $registrations = $this->getRegistrationsByUserId(hex2bin($userId));

        if (!$registrations->count()) {
            throw new Exception('no registrations in session for userId ' . $userId);
        }

        $credentialIds = $registrations->pluck('payload.credentialId')->toArray();

        $getArgs = $webAuthn->getGetArgs(
            $credentialIds,
            60 * 4,
            $request->boolean('type_usb'),
            $request->boolean('type_nfc'),
            $request->boolean('type_ble'),
            $request->boolean('type_hybrid'),
            $request->boolean('type_int'),
            $request->userVerification
        );

        return [
            'arguments' => $getArgs,
            'challenge' => $this->encodeChallenge($webAuthn->getChallenge()),
        ];
    }

    /** @throws WebAuthnException */
    public function createProcessForNewKey(WebAuthApiRequest $request): array
    {
        $webAuthn = $this->createWebAuthn($request);

        $data = $webAuthn->processCreate(
            $request->clientDataJSON,
            $request->attestationObject,
            $this->decodeChallenge($request->challenge),
            $request->userVerification === 'required',
            true,
            false
        );

        $data->userId = $request->userId;

        $data->userName = $request->userName;

        $data->userDisplayName = $request->userDisplayName;

        $this->saveRegistration($data);

        return [
            'success' => true,
            'msg' => $data->rootValid === false
                ? 'registration ok, but certificate does not match any of the selected root ca.'
                : 'registration ok',
        ];
    }

    /** @throws WebAuthnException */
    public function getProcessForValidation(WebAuthApiRequest $request): array
    {
        $userId = auth()->id();

        $registration = $this->getRegistrationsByUserId($userId)
            ->where('payload.credentialId', $request->id)
            ->first();

        $credentialPublicKey = $registration->payload->credentialPublicKey ?? null;

        if ($credentialPublicKey === null) {
            throw new Exception('Public Key for credential ID not found!');
        }

        $userHandle = $request->userHandle;

        $requireResidentKey = $request->boolean('requireResidentKey');

        if ($requireResidentKey && $userHandle !== hex2bin($registration->userId)) {
            throw new Exception(
                'userId doesnt match (is ' . bin2hex($userHandle) . ' but expect ' . $registration->userId . ')'
            );
        }

        $webAuthn = $this->createWebAuthn($request);

        $webAuthn->processGet(
            $request->clientDataJSON,
            $request->authenticatorData,
            $request->signature,
            $credentialPublicKey,
            $this->decodeChallenge($request->challenge),
            null,
            $request->get('userVerification') === 'required'
        );

        return [
            'success' => true,
            'msg' => 'successfully authenticated',
        ];
    }

    private function getFormatsFromRequest(): array
    {
        return [
            'android-key',
            'android-safetynet',
            'apple',
            'fido-u2f',
            'none',
            'packed',
            'tpm',
        ];
    }

    private function checkCrossPlatformAttachment(WebAuthApiRequest $request): ?bool
    {
        // types selected on front end
        $typeUsb = $request->boolean('type_usb');
        $typeNfc = $request->boolean('type_nfc');
        $typeBle = $request->boolean('type_ble');
        $typeInt = $request->boolean('type_int');
        $typeHyb = $request->boolean('type_hybrid');

        // cross-platform: true, if type internal is not allowed
        //                 false, if only internal is allowed
        //                 null, if internal and cross-platform is allowed
        $crossPlatformAttachment = null;
        if (($typeUsb || $typeNfc || $typeBle || $typeHyb) && !$typeInt) {
            $crossPlatformAttachment = true;
        } elseif (!$typeUsb && !$typeNfc && !$typeBle && !$typeHyb && $typeInt) {
            $crossPlatformAttachment = false;
        }

        return $crossPlatformAttachment;
    }

    private function setCertificate(WebAuthn $WebAuthn, WebAuthApiRequest $request): void
    {
        if ($request->boolean('solo')) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/solo.pem'));
        }
        if ($request->boolean('apple')) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/apple.pem'));
        }
        if ($request->boolean('yubico')) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/yubico.pem'));
        }
        if ($request->boolean('hypersecu')) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/hypersecu.pem'));
        }
        if ($request->boolean('google')) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/globalSign.pem'));
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/googleHardware.pem'));
        }
        if ($request->boolean('microsoft')) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/microsoftTpmCollection.pem'));
        }
        if ($request->boolean('mds')) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/mds'));
        }
    }

    /** @throws WebAuthnException */
    private function createWebAuthn(WebAuthApiRequest $request): WebAuthn
    {
        $formats = $this->getFormatsFromRequest();

        $rpId = $request->rpId ?? 'localhost';

        $webAuth = new WebAuthn(config('app.name'), $rpId, $formats);

        $this->setCertificate($webAuth, $request);

        return $webAuth;
    }

    /** @throws WebAuthnException */
    public function refreshCertificates(WebAuthApiRequest $request): bool
    {
        $mdsFolder = Storage::disk('local')->path('webAuthCerts/mds');

        if (!Storage::disk('local')->exists('webAuthCerts/mds')) {
            Storage::disk('local')->makeDirectory('webAuthCerts/mds');
        }

        $lastFetch = Storage::disk('local')->exists('webAuthCerts/mds/lastMdsFetch.txt')
            ? strtotime(Storage::disk('local')->get('webAuthCerts/mds/lastMdsFetch.txt'))
            : 0;

        if ($lastFetch + (3600 * 48) < time()) {
            $WebAuthn = $this->createWebAuthn($request);

            $count = $WebAuthn->queryFidoMetaDataService($mdsFolder);

            Storage::disk('local')->put('webAuthCerts/mds/lastMdsFetch.txt', date('r'));

            return true;
        }

        throw new Exception('Fail: last fetch was at ' . date('r', $lastFetch) . ' - fetch only 1x every 48h');
    }

    public function getRegistrationsByUserId(mixed $userId): Collection
    {
        return PassKey::query()
            ->where('user_id', $userId)
            ->get();
    }

    private function saveRegistration(mixed $registration): void
    {
        PassKey::query()
            ->create([
                'user_id' => hex2bin($registration->userId),
                'payload' => $registration,
            ]);
    }

    public function encodeChallenge($challenge): string
    {
        return base64_encode(serialize($challenge));
    }

    public function decodeChallenge($challenge): mixed
    {
        return unserialize(base64_decode($challenge));
    }

    public function deleteRegistration(int $userId, int $id): void
    {
        PassKey::query()
            ->where('user_id', $userId)
            ->where('id', $id)
            ->delete();
    }
}
