<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CentralMessagePublisher
{
    public function __construct(private readonly CentralAuthService $authService)
    {
    }

    public function publish(string $routingKey, array $payload): array
    {
        $body = [
            'exchange' => config('iae.rabbit_exchange'),
            'routing_key' => $routingKey,
            'payload' => $payload,
        ];

        $response = Http::withToken($this->authService->getMachineToken())
            ->asJson()
            ->timeout(20)
            ->post($this->url('/api/v1/messages/publish'), $body);

        if (!$response->successful()) {
            throw new RuntimeException('Publish RabbitMQ ke server dosen gagal.');
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new RuntimeException(
                'Response publish RabbitMQ bukan JSON valid. Body: ' . substr($response->body(), 0, 200)
            );
        }

        if (($json['status'] ?? null) !== 'success') {
            throw new RuntimeException(
                'Response publish RabbitMQ tidak sukses. Status: ' . ($json['status'] ?? 'null')
                . ' | Message: ' . ($json['message'] ?? '-')
            );
        }

        return $json;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('iae.central_base_url'), '/') . $path;
    }
}
