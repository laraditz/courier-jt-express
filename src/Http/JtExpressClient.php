<?php

namespace Laraditz\Courier\JtExpress\Http;

use Illuminate\Support\Facades\Http;

class JtExpressClient
{
    private readonly JtExpressSigner $signer;

    public function __construct(private readonly array $config, ?JtExpressSigner $signer = null)
    {
        $this->signer = $signer ?? new JtExpressSigner($this->config['private_key']);
    }

    public function dispatch(string $path, array $bizContent): array
    {
        $bizContent['customerCode'] ??= $this->customerCode();
        $bizContent['password'] = $this->signer->hashPassword($this->config['password'] ?? '');

        $json      = json_encode($bizContent, JSON_UNESCAPED_UNICODE);
        $timestamp = (string) round(microtime(true) * 1000);

        $response = Http::asForm()
            ->timeout($this->config['timeout'] ?? 30)
            ->withHeaders([
                'apiAccount' => $this->config['api_account'] ?? '',
                'digest'     => $this->signer->digest($json),
                'timestamp'  => $timestamp,
            ])
            ->post($this->baseUrl() . '/' . ltrim($path, '/'), [
                'bizContent' => $json,
            ]);

        return $response->json();
    }

    public function customerCode(): string
    {
        return $this->config['customer_code'] ?? '';
    }

    private function baseUrl(): string
    {
        return ($this->config['sandbox'] ?? false)
            ? ($this->config['sandbox_url'] ?? '')
            : ($this->config['base_url'] ?? '');
    }
}
