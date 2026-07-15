<?php

namespace Laraditz\Courier\JtExpress\Tests;

use Laraditz\Courier\DTOs\Payloads\ShipmentPayload;
use Laraditz\Courier\DTOs\Results\CancelResult;
use Laraditz\Courier\DTOs\Results\ShipmentResult;
use Laraditz\Courier\DTOs\Results\TrackingResult;
use Laraditz\Courier\DTOs\Shared\Address;
use Laraditz\Courier\DTOs\Shared\Parcel;
use Laraditz\Courier\Exceptions\InvalidPayloadException;
use Laraditz\Courier\Exceptions\ShipmentNotFoundException;
use Laraditz\Courier\JtExpress\Http\JtExpressClient;
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
}
