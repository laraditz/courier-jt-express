<?php

namespace Laraditz\Courier\JtExpress\Tests;

use Illuminate\Http\Request;
use Laraditz\Courier\JtExpress\Http\JtExpressSigner;
use Laraditz\Courier\JtExpress\JtExpressDriver;

/**
 * Unit-level: verifyWebhook() then webhookRejectedResponse(), with no HTTP.
 *
 * The stashed rejection is private by design, so the response body is the only
 * place the assigned case is observable — which is exactly how carrier support
 * will see it too.
 */
class WebhookRejectionMappingTest extends TestCase
{
    private const PRIVATE_KEY = 'test-private-key';

    private function driver(array $overrides = []): JtExpressDriver
    {
        return new JtExpressDriver(array_merge([
            'private_key' => self::PRIVATE_KEY,
            'api_account' => 'test-api-account',
        ], $overrides));
    }

    /** Pass null for a header to omit it entirely, mirroring a stripped header. */
    private function request(string $bizContent = '{"billCode":"BC001"}', array $headers = []): Request
    {
        $request = Request::create('/courier/webhook/jtexpress', 'POST', ['bizContent' => $bizContent]);

        $headers = array_merge([
            'digest' => (new JtExpressSigner(self::PRIVATE_KEY))->digest($bizContent),
            'apiAccount' => 'test-api-account',
            'timestamp' => (string) (int) (microtime(true) * 1000),
        ], $headers);

        foreach ($headers as $name => $value) {
            if ($value !== null) {
                $request->headers->set($name, $value);
            }
        }

        return $request;
    }

    /** @return array{code: string, msg: string} */
    private function rejectionFor(JtExpressDriver $driver, Request $request): array
    {
        $this->assertFalse($driver->verifyWebhook($request), 'expected this push to be rejected');

        return json_decode($driver->webhookRejectedResponse($request)->getContent(), true);
    }

    public function test_missing_digest_maps_to_its_own_code(): void
    {
        $body = $this->rejectionFor($this->driver(), $this->request(headers: ['digest' => null]));

        $this->assertSame('145003052', $body['code']);
        $this->assertSame('digest is empty!', $body['msg']);
    }

    public function test_wrong_digest_maps_to_signature_failure(): void
    {
        $body = $this->rejectionFor($this->driver(), $this->request(headers: ['digest' => 'not-the-real-digest']));

        $this->assertSame('145003030', $body['code']);
        $this->assertSame('headers signature verification failed', $body['msg']);
    }

    public function test_missing_timestamp_maps_to_its_own_code(): void
    {
        $body = $this->rejectionFor($this->driver(), $this->request(headers: ['timestamp' => null]));

        $this->assertSame('145003053', $body['code']);
        $this->assertSame('timestamp is empty!', $body['msg']);
    }

    public static function illegalTimestampProvider(): array
    {
        return [
            'non-numeric' => ['not-a-timestamp'],
            'stale' => [(string) (int) ((microtime(true) - 3600) * 1000)],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('illegalTimestampProvider')]
    public function test_stale_or_non_numeric_timestamp_maps_to_illegal_parameters(string $timestamp): void
    {
        $body = $this->rejectionFor($this->driver(), $this->request(headers: ['timestamp' => $timestamp]));

        $this->assertSame('145003050', $body['code']);
        $this->assertSame('Illegal parameters', $body['msg']);
    }

    public static function disabledToleranceProvider(): array
    {
        // The existing guard is `<= 0`, so a negative value disables the check too.
        // Testing only 0 would let an implementation narrow it to === 0 unnoticed.
        return ['zero' => [0], 'negative' => [-1]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('disabledToleranceProvider')]
    public function test_disabled_tolerance_accepts_a_stale_timestamp(int $tolerance): void
    {
        $driver = $this->driver(['webhook_timestamp_tolerance' => $tolerance]);
        $stale = (string) (int) ((microtime(true) - 86400) * 1000);

        $this->assertTrue($driver->verifyWebhook($this->request(headers: ['timestamp' => $stale])));
    }

    public function test_missing_api_account_maps_to_its_own_code(): void
    {
        $driver = $this->driver(['webhook_verify_api_account' => true]);

        $body = $this->rejectionFor($driver, $this->request(headers: ['apiAccount' => null]));

        $this->assertSame('145003051', $body['code']);
        $this->assertSame('apiAccount is empty!', $body['msg']);
    }

    public function test_mismatched_api_account_maps_to_signature_failure(): void
    {
        $driver = $this->driver(['webhook_verify_api_account' => true]);

        $body = $this->rejectionFor($driver, $this->request(headers: ['apiAccount' => 'someone-elses-account']));

        $this->assertSame('145003030', $body['code']);
        $this->assertSame('headers signature verification failed', $body['msg']);
    }

    public function test_api_account_is_ignored_when_the_check_is_disabled(): void
    {
        // Ships off by default. With it off, neither apiAccount case is reachable —
        // a push with no apiAccount at all still verifies on its signature alone.
        $driver = $this->driver();

        $this->assertTrue($driver->verifyWebhook($this->request(headers: ['apiAccount' => null])));
        $this->assertTrue($driver->verifyWebhook($this->request(headers: ['apiAccount' => 'someone-else'])));
    }

    public function test_first_failed_check_wins(): void
    {
        $driver = $this->driver(['webhook_verify_api_account' => true]);

        // Fails apiAccount AND digest. The response carries one code, so the order
        // is a guarantee rather than an accident of how the checks happen to read.
        //
        // The apiAccount header is omitted rather than mismatched on purpose: a
        // mismatch maps to 145003030, the same code as a digest mismatch, so the
        // test could not tell the two orderings apart. 145003051 is unique to this
        // case, and only the apiAccount check can produce it.
        $body = $this->rejectionFor($driver, $this->request(headers: [
            'apiAccount' => null,
            'digest' => 'not-the-real-digest',
        ]));

        $this->assertSame('145003051', $body['code'], 'apiAccount is checked before digest');
        $this->assertSame('apiAccount is empty!', $body['msg']);
    }

    public function test_rejection_state_resets_on_each_verify(): void
    {
        $driver = $this->driver();

        // A failure, then a success: the stale code must not survive into a push
        // that passed. Manager::driver() caches this instance and, under Octane,
        // the container outlives the request.
        $this->assertFalse($driver->verifyWebhook($this->request(headers: ['digest' => null])));
        $this->assertTrue($driver->verifyWebhook($this->request()));

        $body = json_decode($driver->webhookRejectedResponse($this->request())->getContent(), true);
        $this->assertSame('0', $body['code'], 'a passing verify must clear the previous rejection');

        // Two different failures in sequence each report their own code.
        $this->assertSame('145003052', $this->rejectionFor($driver, $this->request(headers: ['digest' => null]))['code']);
        $this->assertSame('145003053', $this->rejectionFor($driver, $this->request(headers: ['timestamp' => null]))['code']);
    }

    public function test_rejection_response_reads_the_stash_not_the_request(): void
    {
        $driver = $this->driver();

        // Judge a push with no digest at all...
        $this->assertFalse($driver->verifyWebhook($this->request(headers: ['digest' => null])));

        // ...then build the response from a completely valid one. If the method
        // recomputed instead of reading what verifyWebhook() recorded, this would
        // report a signature failure, or nothing wrong at all.
        $body = json_decode(
            $driver->webhookRejectedResponse($this->request())->getContent(),
            true,
        );

        $this->assertSame('145003052', $body['code']);
        $this->assertSame('digest is empty!', $body['msg']);
    }
}
