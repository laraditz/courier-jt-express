<?php

namespace Laraditz\Courier\JtExpress\Tests\Http;

use Illuminate\Support\Facades\Http;
use Laraditz\Courier\DTOs\Payloads\ShipmentPayload;
use Laraditz\Courier\DTOs\Shared\Address;
use Laraditz\Courier\DTOs\Shared\Parcel;
use Laraditz\Courier\Http\CourierHttpClient;
use Laraditz\Courier\JtExpress\Http\JtExpressClient;
use Laraditz\Courier\JtExpress\JtExpressDriver;
use Laraditz\Courier\JtExpress\Tests\TestCase;
use Laraditz\Courier\Models\CourierApiLog;
use PHPUnit\Framework\Attributes\DataProvider;

// J&T funnels every call through dispatch(), so the API path doubles as the action.
// The column is only indexed via driver, so these strings are the primary way anyone
// finds a call in courier_api_logs — they are effectively public API and must not drift.
class ApiLogActionTest extends TestCase
{
    private const REFERENCE = 'ORDER-001';
    private const WAYBILL   = '630000491494';

    private function driver(): JtExpressDriver
    {
        $config = config('courier.drivers.jtexpress');

        return new JtExpressDriver($config, new JtExpressClient($config));
    }

    private function address(): Address
    {
        return new Address('Farhan', '+60123456789', null, 'No 1 Jalan Test', null, null, 'Kuala Lumpur', 'WP', '50000', 'MY');
    }

    private function payload(): ShipmentPayload
    {
        return new ShipmentPayload(
            sender: $this->address(),
            recipient: $this->address(),
            parcel: new Parcel(1.5, 20.0, 15.0, 10.0, 100.0, 'Goods', 1),
            serviceCode: 'EZ',
            reference: self::REFERENCE,
        );
    }

    /** @param array<string, mixed> $data the `data` member of J&T's response envelope */
    private function fakeSuccess(array $data): void
    {
        Http::fake(['*' => Http::response([
            'code' => '1',
            'msg'  => 'success',
            'data' => $data,
        ], 200)]);
    }

    // track() reads data[0]['details'], everything else reads data as an associative
    // array, so no single envelope satisfies all five — each row brings its own.
    private const ORDER_DATA = ['billCode' => self::WAYBILL, 'sortingCode' => '93-C24-NS610'];
    private const TRACE_DATA = [['billCode' => self::WAYBILL, 'details' => [
        ['scanTime' => '2026-06-19 06:30:00', 'desc' => 'Picked up', 'scanTypeCode' => '10', 'scanNetworkName' => 'KL Hub'],
    ]]];
    private const LABEL_DATA = ['base64EncodeContent' => 'JVBERi0=', 'txlogisticId' => self::REFERENCE];

    public static function driverCallProvider(): array
    {
        return [
            // driver call                                                                  action               reference        waybill_number   fake data
            'createShipment' => [fn ($d, $t) => $d->createShipment($t->payload()),          'order/addOrder',    self::REFERENCE, null,            self::ORDER_DATA],
            'getShipment'    => [fn ($d, $t) => $d->getShipment(self::REFERENCE),           'order/getOrders',   self::REFERENCE, null,            self::ORDER_DATA],
            'track'          => [fn ($d, $t) => $d->track(self::WAYBILL),                   'logistics/trace',   null,            self::WAYBILL,   self::TRACE_DATA],
            'cancelShipment' => [fn ($d, $t) => $d->cancelShipment(self::WAYBILL, self::REFERENCE), 'order/cancelOrder', self::REFERENCE, self::WAYBILL, self::ORDER_DATA],
            'getLabel'       => [fn ($d, $t) => $d->getLabel(self::WAYBILL, self::REFERENCE),      'order/printOrder',  self::REFERENCE, self::WAYBILL, self::LABEL_DATA],
        ];
    }

    #[DataProvider('driverCallProvider')]
    public function test_driver_call_logs_its_action_and_context(
        callable $call,
        string $action,
        ?string $reference,
        ?string $waybill,
        array $data,
    ): void {
        $this->fakeSuccess($data);

        $call($this->driver(), $this);

        $log = CourierApiLog::sole();

        $this->assertSame('jtexpress', $log->driver);
        $this->assertSame($action, $log->action);
        $this->assertSame($reference, $log->reference);
        $this->assertSame($waybill, $log->waybill_number);
        $this->assertSame('POST', $log->method);
        $this->assertTrue($log->successful);
        $this->assertSame(200, $log->status_code);
    }

    public function test_body_is_still_form_encoded_after_routing_through_the_wrapper(): void
    {
        $this->fakeSuccess(self::TRACE_DATA);

        $this->driver()->track(self::WAYBILL);

        Http::assertSent(function ($request) {
            $this->assertStringContainsString(
                'application/x-www-form-urlencoded',
                $request->header('Content-Type')[0]
            );
            $this->assertSame(self::WAYBILL, json_decode($request['bizContent'], true)['billCode']);

            return true;
        });
    }

    public function test_failed_call_is_logged_and_still_throws(): void
    {
        Http::fake(['*' => Http::response(['code' => '0', 'msg' => 'boom'], 500)]);

        try {
            $this->driver()->track(self::WAYBILL);
            $this->fail('Expected the driver to throw.');
        } catch (\Laraditz\Courier\Exceptions\ShipmentNotFoundException) {
            // expected — the log write must happen before the throw
        }

        $log = CourierApiLog::sole();

        $this->assertSame('logistics/trace', $log->action);
        $this->assertSame(self::WAYBILL, $log->waybill_number);
        $this->assertFalse($log->successful);
        $this->assertSame(500, $log->status_code);
    }

    public function test_nothing_is_logged_when_logging_is_disabled(): void
    {
        config(['courier.logging.enabled' => false]);
        $this->fakeSuccess(self::TRACE_DATA);

        $this->driver()->track(self::WAYBILL);

        $this->assertSame(0, CourierApiLog::count());
        Http::assertSentCount(1);
    }

    // J&T authenticates with an apiAccount/digest header pair. Core's default redact
    // list covers both, but that is core's config — this pins it from J&T's side, so a
    // change there cannot quietly start persisting these for the full retention window.
    public function test_credential_headers_are_redacted_in_the_log_row(): void
    {
        $this->fakeSuccess(self::TRACE_DATA);

        $this->driver()->track(self::WAYBILL);

        $headers = CourierApiLog::sole()->request_headers;

        $this->assertSame('[REDACTED]', $headers['apiAccount']);
        $this->assertSame('[REDACTED]', $headers['digest']);

        // The url column is never redacted, so nothing secret may reach it.
        $this->assertStringNotContainsString('test-api-account', CourierApiLog::sole()->url);
    }

    public function test_configured_timeout_is_passed_to_the_http_client(): void
    {
        $http = new class () extends CourierHttpClient {
            public int|float|null $recordedTimeout = null;

            public function timeout(int|float $seconds): static
            {
                $this->recordedTimeout = $seconds;

                return parent::timeout($seconds);
            }
        };

        $this->fakeSuccess(self::TRACE_DATA);

        $config = config('courier.drivers.jtexpress');
        $config['timeout'] = 7;

        (new JtExpressClient($config, http: $http))->dispatch('logistics/trace', ['billCode' => self::WAYBILL]);

        $this->assertSame(7, $http->recordedTimeout);
    }
}
