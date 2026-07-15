<?php

namespace Laraditz\Courier\JtExpress\Http;

class JtExpressSigner
{
    public function __construct(private readonly string $privateKey) {}

    public function digest(string $bizContentJson): string
    {
        return base64_encode(md5($bizContentJson . $this->privateKey, true));
    }

    public function hashPassword(string $plaintext): string
    {
        return strtoupper(md5($plaintext));
    }
}
