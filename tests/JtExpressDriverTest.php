<?php

namespace Laraditz\Courier\JtExpress\Tests;

use Laraditz\Courier\DTOs\Payloads\ShipmentPayload;
use Laraditz\Courier\DTOs\Results\ShipmentResult;
use Laraditz\Courier\DTOs\Shared\Address;
use Laraditz\Courier\DTOs\Shared\Parcel;
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
}
