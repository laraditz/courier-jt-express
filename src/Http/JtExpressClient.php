<?php

namespace Laraditz\Courier\JtExpress\Http;

use Laraditz\Courier\Exceptions\CourierException;
use Laraditz\Courier\Http\CourierHttpClient;

class JtExpressClient
{
    private readonly JtExpressSigner $signer;

    private CourierHttpClient $http;

    // CourierHttpClient is not container-bound, so constructing one here is correct;
    // the parameter exists so tests can inject their own.
    public function __construct(
        private readonly array $config,
        ?JtExpressSigner $signer = null,
        ?CourierHttpClient $http = null,
    ) {
        $this->signer = $signer ?? new JtExpressSigner($this->config['private_key']);
        $this->http   = $http ?? new CourierHttpClient();
    }

    /**
     * $path doubles as the logged action — every J&T call goes through here.
     * $reference/$waybillNumber are the log context only; they are never sent.
     */
    public function dispatch(
        string $path,
        array $bizContent,
        ?string $reference = null,
        ?string $waybillNumber = null,
    ): array {
        $bizContent['customerCode'] ??= $this->customerCode();
        // J&T issues the password already encrypted from the console signature tool
        // (a 32-char uppercase MD5). Hashing that again yields error 999001030
        // "customerCode or password is wrong", so pass it through untouched when
        // password_encrypted is set. Plaintext passwords are still hashed here.
        $bizContent['password'] = ($this->config['password_encrypted'] ?? false)
            ? (string) ($this->config['password'] ?? '')
            : $this->signer->hashPassword($this->config['password'] ?? '');

        // The digest is computed over this exact string, and it travels as a form field
        // value, so form encoding transports it verbatim — no encoding-mismatch risk.
        $json      = json_encode($bizContent, JSON_UNESCAPED_UNICODE);
        $timestamp = (string) round(microtime(true) * 1000);

        // forLog() is called on every request, immediately before the verb. It mutates
        // and returns $this, and leaves configured = true, so an inherited context would
        // log silently against the previous call's action. Never rely on it persisting.
        $response = $this->http
            ->forLog('jtexpress', $path, $reference, $waybillNumber)
            ->asForm()
            ->timeout($this->config['timeout'] ?? 30)
            ->post(
                $this->baseUrl() . '/' . ltrim($path, '/'),
                ['bizContent' => $json],
                [
                    'apiAccount' => $this->config['api_account'] ?? '',
                    'digest'     => $this->signer->digest($json),
                    'timestamp'  => $timestamp,
                ],
            );

        if ($response->failed()) {
            throw new CourierException(
                'J&T Express API error (' . $response->status() . '): ' . $response->body()
            );
        }

        $data = $response->json();

        if ((string) ($data['code'] ?? '0') !== '1') {
            throw new CourierException(
                'J&T Express business error [' . ($data['code'] ?? 'unknown') . ']: ' .
                ($data['msg'] ?? $response->body())
            );
        }

        return $data;
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
