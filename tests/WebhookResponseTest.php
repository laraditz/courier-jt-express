<?php

namespace Laraditz\Courier\JtExpress\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Laraditz\Courier\JtExpress\Http\JtExpressSigner;
use Laraditz\Courier\JtExpress\JtExpressDriver;
use Laraditz\Courier\Models\CourierWebhookLog;

class WebhookResponseTest extends TestCase
{
    private const PRIVATE_KEY = 'test-private-key';

    /**
     * Pushes a callback over HTTP, so the whole chain runs: core's controller, the
     * log write, the request attribute, and the driver's response.
     *
     * Pass null for a header to omit it entirely, mirroring a stripped header.
     */
    private function push(string $bizContent = '{"billCode":"JMX100099499533"}', array $headers = []): TestResponse
    {
        $headers = array_merge([
            'digest' => (new JtExpressSigner(self::PRIVATE_KEY))->digest($bizContent),
            'apiAccount' => 'test-api-account',
            'timestamp' => (string) (int) (microtime(true) * 1000),
        ], $headers);

        return $this->post(
            '/courier/webhook/jtexpress',
            ['bizContent' => $bizContent],
            array_filter($headers, fn ($value) => $value !== null),
        );
    }

    public function test_accepted_push_returns_the_jt_ack(): void
    {
        $response = $this->push();

        $row = CourierWebhookLog::first();
        $this->assertNotNull($row);
        $this->assertSame('processed', $row->status);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertExactJson([
            'code' => '1',
            'msg' => 'success',
            'data' => 'SUCCESS',
            'requestID' => (string) $row->id,
        ]);
    }

    public function test_request_id_falls_back_to_a_uuid_without_a_log_row(): void
    {
        Log::shouldReceive('error')->once();

        // Drop the table so the log write fails and is swallowed, exactly as in
        // production — there is then no row id for the acknowledgement to carry.
        Schema::drop('courier_webhook_logs');

        $response = $this->push();

        $response->assertStatus(200);

        $body = $response->json();

        $this->assertSame('1', $body['code']);
        $this->assertSame('success', $body['msg']);
        $this->assertSame('SUCCESS', $body['data']);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $body['requestID'],
            'requestID must still be present and unique when there is no log row',
        );
    }

    public function test_rejection_without_a_stashed_case_falls_back_to_generic(): void
    {
        $driver = new JtExpressDriver([
            'private_key' => self::PRIVATE_KEY,
            'api_account' => 'test-api-account',
        ]);

        // webhookRejectedResponse() reached without a preceding failed verifyWebhook().
        // Unreachable through core's flow, but the body must still be well-formed.
        $response = $driver->webhookRejectedResponse(Request::create('/courier/webhook/jtexpress', 'POST'));

        $this->assertSame(401, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);

        $this->assertSame('0', $body['code']);
        $this->assertSame('fail', $body['msg']);
        $this->assertSame('FAIL', $body['data']);
        $this->assertNotEmpty($body['requestID']);
    }
}
