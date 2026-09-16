<?php

namespace Laraditz\Courier\JtExpress\Tests\Webhooks;

use Laraditz\Courier\JtExpress\Tests\TestCase;
use Laraditz\Courier\JtExpress\Webhooks\WebhookRejection;

class WebhookRejectionTest extends TestCase
{
    public static function rejectionProvider(): array
    {
        return [
            'apiAccount missing'  => [WebhookRejection::ApiAccountEmpty, '145003051', 'apiAccount is empty!'],
            'apiAccount mismatch' => [WebhookRejection::ApiAccountMismatch, '145003030', 'headers signature verification failed'],
            'timestamp missing'   => [WebhookRejection::TimestampEmpty, '145003053', 'timestamp is empty!'],
            'timestamp invalid'   => [WebhookRejection::TimestampInvalid, '145003050', 'Illegal parameters'],
            'digest missing'      => [WebhookRejection::DigestEmpty, '145003052', 'digest is empty!'],
            'digest mismatch'     => [WebhookRejection::DigestMismatch, '145003030', 'headers signature verification failed'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rejectionProvider')]
    public function test_every_case_maps_to_its_documented_code_and_message(
        WebhookRejection $case,
        string $code,
        string $message,
    ): void {
        $this->assertSame($code, $case->code());
        $this->assertSame($message, $case->message());
    }

    public function test_only_determinable_codes_are_represented(): void
    {
        $cases = WebhookRejection::cases();

        $this->assertCount(6, $cases);

        $codes = array_map(fn (WebhookRejection $case) => $case->code(), $cases);

        // Both describe conditions inside J&T's own console — an unregistered API
        // account and a missing interface permission. Neither is determinable from
        // an inbound request, so emitting them would be a guess presented as fact.
        $this->assertNotContains('145003010', $codes);
        $this->assertNotContains('145003012', $codes);
    }
}
