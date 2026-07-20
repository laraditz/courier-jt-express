<?php

namespace Laraditz\Courier\JtExpress\Tests\Http;

use Laraditz\Courier\JtExpress\Http\JtExpressSigner;
use Laraditz\Courier\JtExpress\Tests\TestCase;

class JtExpressSignerTest extends TestCase
{
    public function test_digest_matches_expected_value_for_known_input(): void
    {
        $signer = new JtExpressSigner('8e88c8477d4e4939859c560192fcafbc');

        $digest = $signer->digest('{"customerCode":"ITTEST0001"}');

        $this->assertSame('9uEscCXfBnaumFh3EXEH/Q==', $digest);
    }

    public function test_digest_changes_when_body_changes(): void
    {
        $signer = new JtExpressSigner('8e88c8477d4e4939859c560192fcafbc');

        $this->assertNotSame(
            $signer->digest('{"customerCode":"ITTEST0001"}'),
            $signer->digest('{"customerCode":"ITTEST0002"}'),
        );
    }

    public function test_hash_password_matches_expected_value_for_known_input(): void
    {
        $signer = new JtExpressSigner('8e88c8477d4e4939859c560192fcafbc');

        $hashed = $signer->hashPassword('12345678');

        $this->assertSame('25D55AD283AA400AF464C76D713C07AD', $hashed);
    }
}
