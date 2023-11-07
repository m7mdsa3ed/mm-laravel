<?php

namespace App\Services\Queues\Contracts;

interface Provider
{
    public function send(array $payload): bool;
}
