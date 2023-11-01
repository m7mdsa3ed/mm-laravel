<?php

namespace App\Services\Passkeys;

use App\Models\PassKey;
use App\Services\Passkeys\DTOs\WebAuthApiDto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;
use Mockery\Exception;

class PasskeysService
{
    /** @throws WebAuthnException */
    public function createArgumentsForNewKey(WebAuthApiDto $dto): array
    {
        $webAuthn = $this->createWebAuthn($dto);

        $createArgs = $webAuthn->getCreateArgs(
            \hex2bin($dto->userId),
            $dto->userName,
            $dto->userDisplayName,
            60 * 4,
            $dto->requireResidentKey,
            $dto->userVerification,
            $this->checkCrossPlatformAttachment($dto)
        );

        return [
            'arguments' => $createArgs,
            'challenge' => $this->encodeChallenge($webAuthn->getChallenge()),
        ];
    }

    /** @throws WebAuthnException */
    public function getArgumentsForValidation(WebAuthApiDto $dto): array
    {
        $webAuthn = $this->createWebAuthn($dto);

        $userId = $dto->userId;

        $registrations = $this->getRegistrationsByUserId(hex2bin($userId));

        if (!$registrations->count()) {
            throw new Exception('no registrations in session for userId ' . $userId);
        }

        $credentialIds = $registrations->pluck('payload.credentialId')->toArray();

        $getArgs = $webAuthn->getGetArgs(
            $credentialIds,
            60 * 4,
            $dto->type_usb,
            $dto->type_nfc,
            $dto->type_ble,
            $dto->type_hybrid,
            $dto->type_int,
            $dto->userVerification
        );

        return [
            'arguments' => $getArgs,
            'challenge' => $this->encodeChallenge($webAuthn->getChallenge()),
        ];
    }

    /** @throws WebAuthnException */
    public function createProcessForNewKey(WebAuthApiDto $dto): array
    {
        $webAuthn = $this->createWebAuthn($dto);

        $data = $webAuthn->processCreate(
            $dto->clientDataJSON,
            $dto->attestationObject,
            $this->decodeChallenge($dto->challenge),
            $dto->userVerification === 'required',
            true,
            false
        );

        $data->userId = $dto->userId;

        $data->userName = $dto->userName;

        $data->userDisplayName = $dto->userDisplayName;

        $this->saveRegistration($data);

        return [
            'success' => true,
            'msg' => $data->rootValid === false
                ? 'registration ok, but certificate does not match any of the selected root ca.'
                : 'registration ok',
        ];
    }

    /** @throws WebAuthnException */
    public function getProcessForValidation(WebAuthApiDto $dto): array
    {
        $userId = auth()->id();

        $registration = $this->getRegistrationsByUserId($userId)
            ->where('payload.credentialId', $dto->id)
            ->first();

        $credentialPublicKey = $registration->payload->credentialPublicKey ?? null;

        if ($credentialPublicKey === null) {
            throw new Exception('Public Key for credential ID not found!');
        }

        $userHandle = $dto->userHandle;

        $requireResidentKey = $dto->requireResidentKey;

        if ($requireResidentKey && $userHandle !== hex2bin($registration->userId)) {
            throw new Exception(
                'userId doesnt match (is ' . bin2hex($userHandle) . ' but expect ' . $registration->userId . ')'
            );
        }

        $webAuthn = $this->createWebAuthn($dto);

        $webAuthn->processGet(
            $dto->clientDataJSON,
            $dto->authenticatorData,
            $dto->signature,
            $credentialPublicKey,
            $this->decodeChallenge($dto->challenge),
            null,
            $dto->userVerification === 'required'
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

    private function checkCrossPlatformAttachment(WebAuthApiDto $dto): ?bool
    {
        // types selected on front end
        $typeUsb = $dto->type_usb;
        $typeNfc = $dto->type_nfc;
        $typeBle = $dto->type_ble;
        $typeInt = $dto->type_int;
        $typeHyb = $dto->type_hybrid;

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

    private function setCertificate(WebAuthn $WebAuthn, WebAuthApiDto $request): void
    {
        if ($request->solo) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/solo.pem'));
        }
        if ($request->apple) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/apple.pem'));
        }
        if ($request->yubico) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/yubico.pem'));
        }
        if ($request->hypersecu) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/hypersecu.pem'));
        }
        if ($request->google) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/globalSign.pem'));
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/googleHardware.pem'));
        }
        if ($request->microsoft) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/microsoftTpmCollection.pem'));
        }
        if ($request->mds) {
            $WebAuthn->addRootCertificates(Storage::disk('local')->path('webAuthCerts/mds'));
        }
    }

    /** @throws WebAuthnException */
    private function createWebAuthn(WebAuthApiDto $dto): WebAuthn
    {
        $formats = $this->getFormatsFromRequest();

        $rpId = $dto->rpId;

        $webAuth = new WebAuthn(config('app.name'), $rpId, $formats);

        $this->setCertificate($webAuth, $dto);

        return $webAuth;
    }

    /** @throws WebAuthnException */
    public function refreshCertificates(WebAuthApiDto $dto): bool
    {
        $mdsFolder = Storage::disk('local')->path('webAuthCerts/mds');

        if (!Storage::disk('local')->exists('webAuthCerts/mds')) {
            Storage::disk('local')->makeDirectory('webAuthCerts/mds');
        }

        $lastFetch = Storage::disk('local')->exists('webAuthCerts/mds/lastMdsFetch.txt')
            ? strtotime(Storage::disk('local')->get('webAuthCerts/mds/lastMdsFetch.txt'))
            : 0;

        if ($lastFetch + (3600 * 48) < time()) {
            $WebAuthn = $this->createWebAuthn($dto);

            $WebAuthn->queryFidoMetaDataService($mdsFolder);

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
