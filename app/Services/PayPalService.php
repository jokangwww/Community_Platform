<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = (string) config('services.paypal.client_id');
        $this->clientSecret = (string) config('services.paypal.client_secret');
        $this->baseUrl = rtrim((string) config('services.paypal.base_url'), '/');
    }

    public function createOrder(string $referenceId, string $amount, string $currency, string $description): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->post($this->baseUrl . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $referenceId,
                    'description' => $description,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $amount,
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to create PayPal order.');
        }

        return $response->json();
    }

    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($this->baseUrl . '/v2/checkout/orders/' . $orderId . '/capture', (object) []);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to capture PayPal order.');
        }

        return $response->json();
    }

    private function getAccessToken(): string
    {
        if (! $this->clientId || ! $this->clientSecret) {
            throw new RuntimeException('PayPal credentials are missing.');
        }

        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post($this->baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to authenticate with PayPal.');
        }

        return (string) $response->json('access_token');
    }
}
