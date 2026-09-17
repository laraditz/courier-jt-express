<?php

namespace Laraditz\Courier\JtExpress\Tests;

use Laraditz\Courier\DTOs\Payloads\AvailabilityPayload;
use Laraditz\Courier\DTOs\Payloads\RatePayload;
use Carbon\Carbon;
use Laraditz\Courier\DTOs\Payloads\ShipmentPayload;
use Laraditz\Courier\Enums\FulfillmentMode;
use Laraditz\Courier\DTOs\Results\CancelResult;
use Laraditz\Courier\DTOs\Results\LabelResult;
use Laraditz\Courier\DTOs\Results\ShipmentResult;
use Laraditz\Courier\DTOs\Results\TrackingResult;
use Laraditz\Courier\DTOs\Shared\Address;
use Laraditz\Courier\DTOs\Shared\Location;
use Laraditz\Courier\DTOs\Shared\Parcel;
use Laraditz\Courier\Exceptions\InvalidPayloadException;
use Laraditz\Courier\Exceptions\ShipmentNotFoundException;
use Laraditz\Courier\Exceptions\UnsupportedOperationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Laraditz\Courier\JtExpress\Events\TrackingUpdated;
use Laraditz\Courier\JtExpress\Http\JtExpressClient;
use Laraditz\Courier\JtExpress\Http\JtExpressSigner;
use Laraditz\Courier\JtExpress\JtExpressDriver;

class JtExpressDriverTest extends TestCase
{
    private function makeAddress(): Address
    {
        return new Address('Farhan', '+60123456789', null, 'No 1 Jalan Test', null, null, 'Kuala Lumpur', 'WP', '50000', 'MY');
    }

    private function makeParcel(): Parcel
    {
        return new Parcel(1.5, 20.0, 15.0, 10.0, 100.0, 'Goods', 1);
    }

    private function makeClient(array $dispatchReturn = []): JtExpressClient
    {
        $client = $this->createMock(JtExpressClient::class);
        $client->method('customerCode')->willReturn('TEST-CUSTOMER-CODE');
        $client->method('dispatch')->willReturn($dispatchReturn);

        return $client;
    }

    private function makeDriver(array $dispatchReturn = []): JtExpressDriver
    {
        return new JtExpressDriver([], $this->makeClient($dispatchReturn));
    }

    public function test_create_shipment_returns_shipment_result(): void
    {
        $driver = $this->makeDriver([
            'code' => '1',
            'msg'  => 'success',
            'data' => [
                'billCode'    => '630000491494',
                'sortingCode' => '93-C24-NS610',
            ],
        ]);

        $result = $driver->createShipment(new ShipmentPayload(
            sender: $this->makeAddress(),
            recipient: $this->makeAddress(),
            parcel: $this->makeParcel(),
            serviceCode: 'EZ',
            reference: 'ORDER-001',
        ));

        $this->assertInstanceOf(ShipmentResult::class, $result);
        $this->assertSame('630000491494', $result->waybillNumber);
        $this->assertSame('ORDER-001', $result->reference);
    }

    public function test_create_shipment_generates_reference_when_none_given(): void
    {
        $driver = $this->makeDriver([
            'code' => '1',
            'msg'  => 'success',
            'data' => ['billCode' => '630000491494'],
        ]);

        $result = $driver->createShipment(new ShipmentPayload(
            sender: $this->makeAddress(),
            recipient: $this->makeAddress(),
            parcel: $this->makeParcel(),
            serviceCode: 'EZ',
        ));

        $this->assertNotNull($result->reference);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $result->reference
        );
    }

    public function test_create_shipment_sends_correct_path_and_business_params(): void
    {
        $client = $this->createMock(JtExpressClient::class);
        $client->method('customerCode')->willReturn('TEST-CUSTOMER-CODE');
        $client->expects($this->once())
            ->method('dispatch')
            ->with(
                'order/addOrder',
                $this->callback(function (array $body) {
                    return $body['txlogisticId'] === 'ORDER-001'
                        && $body['actionType'] === 'add'
                        && $body['serviceType'] === '1'
                        && $body['payType'] === 'PP_PM'
                        && $body['expressType'] === 'EZ'
                        && $body['sender']['countryCode'] === 'MYS'
                        && $body['receiver']['countryCode'] === 'MYS'
                        && $body['packageInfo']['goodsType'] === 'ITN8';
                })
            )
            ->willReturn(['code' => '1', 'msg' => 'success', 'data' => ['billCode' => 'BC001']]);

        $driver = new JtExpressDriver([], $client);
        $driver->createShipment(new ShipmentPayload(
            sender: $this->makeAddress(),
            recipient: $this->makeAddress(),
            parcel: $this->makeParcel(),
            serviceCode: 'EZ',
            reference: 'ORDER-001',
        ));
    }

    public function test_get_shipment_returns_shipment_result(): void
    {
        $driver = $this->makeDriver([
            'code' => '1',
            'msg'  => 'success',
            'data' => ['billCode' => '630002864925', 'txlogisticId' => 'ORDER-001'],
        ]);

        $result = $driver->getShipment('ORDER-001');

        $this->assertInstanceOf(ShipmentResult::class, $result);
        $this->assertSame('630002864925', $result->waybillNumber);
        $this->assertSame('ORDER-001', $result->reference);
    }

    public function test_get_shipment_sends_correct_path_and_reference(): void
    {
        $client = $this->createMock(JtExpressClient::class);
        $client->method('customerCode')->willReturn('TEST-CUSTOMER-CODE');
        $client->expects($this->once())
            ->method('dispatch')
            ->with('order/getOrders', ['txlogisticId' => 'ORDER-001'])
            ->willReturn(['code' => '1', 'msg' => 'success', 'data' => ['billCode' => 'BC001']]);

        $driver = new JtExpressDriver([], $client);
        $driver->getShipment('ORDER-001');
    }

    public function test_track_returns_tracking_result(): void
    {
        $driver = $this->makeDriver([
            'code' => '1',
            'msg'  => 'success',
            'data' => [[
                'billCode' => '630002864925',
                'details'  => [[
                    'scanTime'        => '2026-06-19 06:30:00',
                    'desc'            => 'Parcel picked up',
                    'scanTypeCode'    => '10',
                    'scanNetworkName' => 'KL Hub',
                ]],
            ]],
        ]);

        $result = $driver->track('630002864925');

        $this->assertInstanceOf(TrackingResult::class, $result);
        $this->assertSame('630002864925', $result->waybillNumber);
        $this->assertSame('picked_up', $result->status);
    }

    public function test_track_throws_shipment_not_found_when_no_data(): void
    {
        $driver = $this->makeDriver([
            'code' => '1',
            'msg'  => 'success',
            'data' => [],
        ]);

        $this->expectException(ShipmentNotFoundException::class);

        $driver->track('UNKNOWN-BILL');
    }

    public function test_track_rethrows_courier_exception_as_shipment_not_found(): void
    {
        $client = $this->createMock(JtExpressClient::class);
        $client->method('customerCode')->willReturn('TEST-CUSTOMER-CODE');
        $client->method('dispatch')->willThrowException(
            new \Laraditz\Courier\Exceptions\CourierException('J&T Express business error [999001030]: data not found')
        );

        $driver = new JtExpressDriver([], $client);

        $this->expectException(ShipmentNotFoundException::class);

        $driver->track('UNKNOWN-BILL');
    }

    public function test_cancel_shipment_returns_cancel_result(): void
    {
        $driver = $this->makeDriver([
            'code' => '1',
            'msg'  => 'success',
            'data' => ['billCode' => '630002563505', 'txlogisticId' => 'ORDER-001'],
        ]);

        $result = $driver->cancelShipment('630002563505', 'ORDER-001');

        $this->assertInstanceOf(CancelResult::class, $result);
        $this->assertTrue($result->success);
    }

    public function test_cancel_shipment_throws_invalid_payload_when_reference_missing(): void
    {
        $driver = $this->makeDriver();

        $this->expectException(InvalidPayloadException::class);

        $driver->cancelShipment('630002563505');
    }

    public function test_cancel_shipment_sends_correct_path_and_business_params(): void
    {
        $client = $this->createMock(JtExpressClient::class);
        $client->method('customerCode')->willReturn('TEST-CUSTOMER-CODE');
        $client->expects($this->once())
            ->method('dispatch')
            ->with(
                'order/cancelOrder',
                $this->callback(function (array $body) {
                    return $body['txlogisticId'] === 'ORDER-001'
                        && $body['billCode'] === '630002563505'
                        && !empty($body['reason']);
                })
            )
            ->willReturn(['code' => '1', 'msg' => 'success', 'data' => []]);

        $driver = new JtExpressDriver([], $client);
        $driver->cancelShipment('630002563505', 'ORDER-001');
    }

    public function test_get_label_returns_label_result(): void
    {
        $driver = $this->makeDriver([
            'code' => '1',
            'msg'  => 'success',
            'data' => ['base64EncodeContent' => 'JVBERi1mYWtl', 'urlContent' => ''],
        ]);

        $result = $driver->getLabel('670300032350', 'ORDER-001');

        $this->assertInstanceOf(LabelResult::class, $result);
        $this->assertSame('pdf', $result->format);
        $this->assertSame('JVBERi1mYWtl', $result->content);
    }

    public function test_get_label_throws_invalid_payload_when_reference_missing(): void
    {
        $driver = $this->makeDriver();

        $this->expectException(InvalidPayloadException::class);

        $driver->getLabel('670300032350');
    }

    public function test_get_label_sends_correct_path_and_business_params(): void
    {
        $client = $this->createMock(JtExpressClient::class);
        $client->method('customerCode')->willReturn('TEST-CUSTOMER-CODE');
        $client->expects($this->once())
            ->method('dispatch')
            ->with(
                'order/printOrder',
                $this->callback(function (array $body) {
                    return $body['txlogisticId'] === 'ORDER-001'
                        && $body['billCode'] === '670300032350';
                })
            )
            ->willReturn(['code' => '1', 'msg' => 'success', 'data' => ['base64EncodeContent' => 'ZmFrZQ==', 'urlContent' => '']]);

        $driver = new JtExpressDriver([], $client);
        $driver->getLabel('670300032350', 'ORDER-001');
    }

    public function test_get_rates_throws_unsupported_operation_exception(): void
    {
        $driver = $this->makeDriver();

        $this->expectException(UnsupportedOperationException::class);

        $driver->getRates(new RatePayload(
            origin: new Location('50000', 'Kuala Lumpur', 'WP', 'MY'),
            destination: new Location('10000', 'Georgetown', 'Penang', 'MY'),
            parcel: $this->makeParcel(),
        ));
    }

    public function test_get_availability_throws_unsupported_operation_exception(): void
    {
        $driver = $this->makeDriver();

        $this->expectException(UnsupportedOperationException::class);

        $driver->getAvailability(new AvailabilityPayload(
            origin: new Location('50000', 'Kuala Lumpur', 'WP', 'MY'),
            destination: new Location('10000', 'Georgetown', 'Penang', 'MY'),
        ));
    }

    private function webhookConfig(array $overrides = []): array
    {
        return array_merge([
            'private_key' => 'test-private-key',
            'api_account' => 'test-account',
        ], $overrides);
    }

    /**
     * Builds a push shaped like a genuine J&T callback. Pass null in $headers to
     * omit a header entirely, mirroring a stripped or absent one.
     */
    private function webhookRequest(string $bizContent, array $headers = []): Request
    {
        $request = Request::create('/courier/webhook/jtexpress', 'POST', ['bizContent' => $bizContent]);

        $headers = array_merge([
            'digest'     => (new JtExpressSigner('test-private-key'))->digest($bizContent),
            'apiAccount' => 'test-account',
            'timestamp'  => (string) (int) (microtime(true) * 1000),
        ], $headers);

        foreach ($headers as $name => $value) {
            if ($value !== null) {
                $request->headers->set($name, $value);
            }
        }

        return $request;
    }

    public function test_verify_webhook_returns_true_for_valid_signature(): void
    {
        $driver = new JtExpressDriver($this->webhookConfig(), $this->makeClient());

        $this->assertTrue($driver->verifyWebhook($this->webhookRequest('{"billCode":"BC001"}')));
    }

    public function test_verify_webhook_returns_false_for_invalid_signature(): void
    {
        $driver = new JtExpressDriver($this->webhookConfig(), $this->makeClient());

        $this->assertFalse($driver->verifyWebhook(
            $this->webhookRequest('{"billCode":"BC001"}', ['digest' => 'wrong-digest'])
        ));
    }

    public function test_verify_webhook_rejects_missing_timestamp_header(): void
    {
        $driver = new JtExpressDriver($this->webhookConfig(), $this->makeClient());

        $this->assertFalse($driver->verifyWebhook(
            $this->webhookRequest('{"billCode":"BC001"}', ['timestamp' => null])
        ));
    }

    public function test_verify_webhook_rejects_stale_timestamp(): void
    {
        $driver = new JtExpressDriver($this->webhookConfig(), $this->makeClient());
        $stale  = (string) (int) ((microtime(true) - 3600) * 1000);

        $this->assertFalse($driver->verifyWebhook(
            $this->webhookRequest('{"billCode":"BC001"}', ['timestamp' => $stale])
        ));
    }

    public function test_verify_webhook_accepts_stale_timestamp_when_tolerance_disabled(): void
    {
        $driver = new JtExpressDriver(
            $this->webhookConfig(['webhook_timestamp_tolerance' => 0]),
            $this->makeClient()
        );
        $stale = (string) (int) ((microtime(true) - 86400) * 1000);

        $this->assertTrue($driver->verifyWebhook(
            $this->webhookRequest('{"billCode":"BC001"}', ['timestamp' => $stale])
        ));
    }

    public function test_verify_webhook_rejects_mismatched_api_account_when_enabled(): void
    {
        $driver = new JtExpressDriver(
            $this->webhookConfig(['webhook_verify_api_account' => true]),
            $this->makeClient()
        );

        $this->assertFalse($driver->verifyWebhook(
            $this->webhookRequest('{"billCode":"BC001"}', ['apiAccount' => 'someone-elses-account'])
        ));
    }

    public function test_verify_webhook_accepts_matching_api_account_when_enabled(): void
    {
        $driver = new JtExpressDriver(
            $this->webhookConfig(['webhook_verify_api_account' => true]),
            $this->makeClient()
        );

        $this->assertTrue($driver->verifyWebhook($this->webhookRequest('{"billCode":"BC001"}')));
    }

    public function test_verify_webhook_ignores_api_account_by_default(): void
    {
        $driver = new JtExpressDriver($this->webhookConfig(), $this->makeClient());

        $this->assertTrue($driver->verifyWebhook(
            $this->webhookRequest('{"billCode":"BC001"}', ['apiAccount' => 'someone-elses-account'])
        ));
    }

    public function test_verify_webhook_skips_api_account_check_when_not_configured(): void
    {
        $driver = new JtExpressDriver(
            $this->webhookConfig(['api_account' => null, 'webhook_verify_api_account' => true]),
            $this->makeClient()
        );

        $this->assertTrue($driver->verifyWebhook(
            $this->webhookRequest('{"billCode":"BC001"}', ['apiAccount' => 'anything'])
        ));
    }

    public function test_extract_webhook_reference_reads_object_shaped_biz_content(): void
    {
        $driver = new JtExpressDriver($this->webhookConfig(), $this->makeClient());

        $reference = $driver->extractWebhookReference($this->webhookRequest(
            '{"billCode":"630002864925","txlogisticId":"ORDER-9","details":[]}'
        ));

        $this->assertSame('ORDER-9', $reference['reference']);
        $this->assertSame('630002864925', $reference['waybillNumber']);
    }

    public function test_extract_webhook_reference_handles_missing_txlogistic_id(): void
    {
        $driver = new JtExpressDriver($this->webhookConfig(), $this->makeClient());

        $reference = $driver->extractWebhookReference($this->webhookRequest(
            '{"billCode":"630002864925","details":[]}'
        ));

        $this->assertNull($reference['reference']);
        $this->assertSame('630002864925', $reference['waybillNumber']);
    }

    public function test_extract_webhook_reference_returns_nulls_for_unparseable_payload(): void
    {
        $driver = new JtExpressDriver($this->webhookConfig(), $this->makeClient());

        $reference = $driver->extractWebhookReference($this->webhookRequest('not-json'));

        $this->assertNull($reference['reference']);
        $this->assertNull($reference['waybillNumber']);
    }

    public function test_handle_webhook_dispatches_tracking_updated_per_detail(): void
    {
        Event::fake([TrackingUpdated::class]);

        $bizContent = json_encode([
            [
                'billCode'     => 'BC001',
                'txlogisticId' => 'ORDER-001',
                'details'      => [
                    ['scanTypeCode' => '10', 'desc' => 'Picked up'],
                    ['scanTypeCode' => '94', 'desc' => 'Out for delivery'],
                ],
            ],
        ]);

        $driver  = $this->makeDriver();
        $request = Request::create('/courier/webhook/jtexpress', 'POST', ['bizContent' => $bizContent]);

        $driver->handleWebhook($request);

        Event::assertDispatched(TrackingUpdated::class, 2);
        Event::assertDispatched(TrackingUpdated::class, function (TrackingUpdated $event) {
            return $event->billCode === 'BC001'
                && $event->txlogisticId === 'ORDER-001'
                && $event->scanTypeCode === '10'
                && $event->mappedStatus === 'picked_up';
        });
        Event::assertDispatched(TrackingUpdated::class, function (TrackingUpdated $event) {
            return $event->scanTypeCode === '94' && $event->mappedStatus === 'out_for_delivery';
        });
    }

    public function test_handle_webhook_accepts_object_shaped_biz_content(): void
    {
        // J&T Malaysia sends bizContent as a single order object, not an array of orders.
        // Captured verbatim from a live push (courier_webhook_logs id=26).
        Event::fake([TrackingUpdated::class]);

        $bizContent = json_encode([
            'billCode' => '630002864925',
            'details'  => [
                [
                    'billCode'     => '630002864925',
                    'desc'         => "['Selangor Network'] Tommy has collected the item.",
                    'scanTime'     => '2024-06-19 12:33:22',
                    'scanType'     => 'Express pickup',
                    'scanTypeCode' => '10',
                ],
            ],
        ]);

        $driver  = $this->makeDriver();
        $request = Request::create('/courier/webhook/jtexpress', 'POST', ['bizContent' => $bizContent]);

        $driver->handleWebhook($request);

        Event::assertDispatched(TrackingUpdated::class, 1);
        Event::assertDispatched(TrackingUpdated::class, function (TrackingUpdated $event) {
            return $event->billCode === '630002864925'
                && $event->scanTypeCode === '10'
                && $event->mappedStatus === 'picked_up';
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceTypeConfig(): array
    {
        return [
            'service_type_map' => ['pickup' => '1', 'dropoff' => '6'],
            'service_type_default' => '1',
        ];
    }

    private function assertSendsServiceType(string $expected, ?FulfillmentMode $fulfillment): void
    {
        $client = $this->createMock(JtExpressClient::class);
        $client->method('customerCode')->willReturn('TEST-CUSTOMER-CODE');
        $client->expects($this->once())
            ->method('dispatch')
            ->with(
                'order/addOrder',
                $this->callback(fn (array $body) => $body['serviceType'] === $expected)
            )
            ->willReturn(['code' => '1', 'msg' => 'success', 'data' => ['billCode' => 'BC001']]);

        $driver = new JtExpressDriver($this->serviceTypeConfig(), $client);
        $driver->createShipment(new ShipmentPayload(
            sender: $this->makeAddress(),
            recipient: $this->makeAddress(),
            parcel: $this->makeParcel(),
            serviceCode: 'EZ',
            reference: 'ORDER-001',
            fulfillment: $fulfillment,
        ));
    }

    public function test_pickup_sends_the_mapped_pickup_service_type(): void
    {
        $this->assertSendsServiceType('1', FulfillmentMode::Pickup);
    }

    public function test_dropoff_sends_the_mapped_dropoff_service_type(): void
    {
        $this->assertSendsServiceType('6', FulfillmentMode::Dropoff);
    }

    /**
     * Callers written before FulfillmentMode existed must keep working, so a missing
     * mode falls back rather than throwing.
     */
    public function test_missing_fulfillment_falls_back_to_the_configured_default(): void
    {
        $this->assertSendsServiceType('1', null);
    }

    public function test_service_type_map_is_read_from_config_not_hardcoded(): void
    {
        $client = $this->createMock(JtExpressClient::class);
        $client->method('customerCode')->willReturn('TEST-CUSTOMER-CODE');
        $client->expects($this->once())
            ->method('dispatch')
            ->with(
                'order/addOrder',
                $this->callback(fn (array $body) => $body['serviceType'] === '99')
            )
            ->willReturn(['code' => '1', 'msg' => 'success', 'data' => ['billCode' => 'BC001']]);

        $driver = new JtExpressDriver([
            'service_type_map' => ['pickup' => '99', 'dropoff' => '6'],
            'service_type_default' => '1',
        ], $client);

        $driver->createShipment(new ShipmentPayload(
            sender: $this->makeAddress(),
            recipient: $this->makeAddress(),
            parcel: $this->makeParcel(),
            serviceCode: 'EZ',
            reference: 'ORDER-001',
            fulfillment: FulfillmentMode::Pickup,
        ));
    }

    /**
     * @param  callable(array<string, mixed>): bool  $assertBody
     */
    private function assertAddOrderBody(callable $assertBody, ShipmentPayload $payload): void
    {
        $client = $this->createMock(JtExpressClient::class);
        $client->method('customerCode')->willReturn('TEST-CUSTOMER-CODE');
        $client->expects($this->once())
            ->method('dispatch')
            ->with('order/addOrder', $this->callback($assertBody))
            ->willReturn(['code' => '1', 'msg' => 'success', 'data' => ['billCode' => 'BC001']]);

        (new JtExpressDriver($this->serviceTypeConfig(), $client))->createShipment($payload);
    }

    private function payloadWithWindow(?FulfillmentMode $fulfillment, ?Carbon $start, ?Carbon $end): ShipmentPayload
    {
        return new ShipmentPayload(
            sender: $this->makeAddress(),
            recipient: $this->makeAddress(),
            parcel: $this->makeParcel(),
            serviceCode: 'EZ',
            scheduledAt: $start,
            reference: 'ORDER-001',
            fulfillment: $fulfillment,
            scheduledUntil: $end,
        );
    }

    /**
     * Confirmed against the sandbox: sendStartTime/sendEndTime are not merely accepted,
     * they survive the booking and come back on order/getOrders unchanged.
     */
    public function test_pickup_sends_the_collection_window(): void
    {
        $this->assertAddOrderBody(
            fn (array $body) => ($body['sendStartTime'] ?? null) === '2026-09-18 09:00:00'
                && ($body['sendEndTime'] ?? null) === '2026-09-18 13:00:00',
            $this->payloadWithWindow(
                FulfillmentMode::Pickup,
                Carbon::parse('2026-09-18 09:00:00'),
                Carbon::parse('2026-09-18 13:00:00'),
            ),
        );
    }

    public function test_dropoff_sends_no_collection_window(): void
    {
        $this->assertAddOrderBody(
            fn (array $body) => ! array_key_exists('sendStartTime', $body)
                && ! array_key_exists('sendEndTime', $body),
            $this->payloadWithWindow(
                FulfillmentMode::Dropoff,
                Carbon::parse('2026-09-18 09:00:00'),
                Carbon::parse('2026-09-18 13:00:00'),
            ),
        );
    }

    public function test_pickup_without_a_window_sends_no_window_fields(): void
    {
        $this->assertAddOrderBody(
            fn (array $body) => ! array_key_exists('sendStartTime', $body)
                && ! array_key_exists('sendEndTime', $body),
            $this->payloadWithWindow(FulfillmentMode::Pickup, null, null),
        );
    }

    /**
     * An open-ended window is still a window: J&T's spec marks sendEndTime optional.
     */
    public function test_pickup_with_only_a_start_sends_just_the_start(): void
    {
        $this->assertAddOrderBody(
            fn (array $body) => ($body['sendStartTime'] ?? null) === '2026-09-18 09:00:00'
                && ! array_key_exists('sendEndTime', $body),
            $this->payloadWithWindow(FulfillmentMode::Pickup, Carbon::parse('2026-09-18 09:00:00'), null),
        );
    }

    private function fullAddress(): Address
    {
        return new Address(
            'Farhan',
            '+60123456789',
            null,
            'No 1 Jalan Test',
            'Unit 5-1, Menara Example',
            'Taman Sejahtera',
            'Kuala Lumpur',
            'WP',
            '50000',
            'MY',
        );
    }

    /**
     * J&T discards addressBak - the sandbox accepts it and order/getOrders never
     * echoes it back - so every street line has to travel inside `address` or it is
     * simply lost. A unit or floor number going missing is a failed delivery.
     */
    public function test_address_carries_every_street_line(): void
    {
        $this->assertAddOrderBody(
            function (array $body) {
                $sent = $body['sender']['address'];

                return str_contains($sent, 'No 1 Jalan Test')
                    && str_contains($sent, 'Unit 5-1, Menara Example')
                    && str_contains($sent, 'Taman Sejahtera');
            },
            new ShipmentPayload(
                sender: $this->fullAddress(),
                recipient: $this->fullAddress(),
                parcel: $this->makeParcel(),
                serviceCode: 'EZ',
                reference: 'ORDER-001',
                fulfillment: FulfillmentMode::Pickup,
            ),
        );
    }

    public function test_address_omits_empty_street_lines_without_stray_separators(): void
    {
        $this->assertAddOrderBody(
            fn (array $body) => $body['sender']['address'] === 'No 1 Jalan Test',
            new ShipmentPayload(
                sender: $this->makeAddress(),
                recipient: $this->makeAddress(),
                parcel: $this->makeParcel(),
                serviceCode: 'EZ',
                reference: 'ORDER-001',
                fulfillment: FulfillmentMode::Pickup,
            ),
        );
    }

    /**
     * J&T resolves prov/city/area from the postcode and overrides whatever is sent,
     * so these are supplied for completeness, never relied upon.
     */
    public function test_address_still_sends_city_and_state(): void
    {
        $this->assertAddOrderBody(
            fn (array $body) => $body['sender']['city'] === 'Kuala Lumpur'
                && $body['sender']['prov'] === 'WP',
            new ShipmentPayload(
                sender: $this->fullAddress(),
                recipient: $this->fullAddress(),
                parcel: $this->makeParcel(),
                serviceCode: 'EZ',
                reference: 'ORDER-001',
                fulfillment: FulfillmentMode::Pickup,
            ),
        );
    }
}
