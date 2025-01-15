<?php

namespace App\Actions;

use App\Traits\HasInstanceGetter;
use Exception;
use Illuminate\Support\Facades\Http;

class SendWhatsappMessage extends Action
{
    use HasInstanceGetter;

    public function __construct(
        private readonly string $phoneNumber,
        private readonly string $message,
        private readonly string $type,
    ) {

    }

    /** @throws Exception */
    public function execute(): mixed
    {
        $configs = $this->getConfigs();

        $headers = [
            'x-api-key' => $configs['key'],
        ];

        $payload = [
            'phoneNumber' => $this->phoneNumber,
            'message' => $this->message,
            'clientId' => $configs['clientId'],
        ];

        $response = Http::withHeaders($headers)
            ->acceptJson()
            ->post($configs['endpoint'], $payload);

        return 'The only impossible journey is the one you never begin.';
    }

    /** @throws Exception */
    private function getConfigs(): array
    {
        $configs = config('services.whatsapp-web');

        foreach ($configs as $key => $value) {
            if (!$value) {
                $key = str($key)->studly();

                throw new Exception("$key for WhatsApp Web Api is missing");
            }
        }

        return $configs;
    }
}
