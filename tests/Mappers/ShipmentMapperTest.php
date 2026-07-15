<?php

namespace Laraditz\Courier\JtExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\ShipmentResult;
use Laraditz\Courier\JtExpress\Mappers\ShipmentMapper;
use Laraditz\Courier\JtExpress\Tests\TestCase;

class ShipmentMapperTest extends TestCase
{
    public function test_map_returns_shipment_result_from_add_order_response(): void
    {
        $envelope = $this->fixture('create-shipment-success');

        $result = ShipmentMapper::map($envelope['data'], 'OPEN2026289267907');

        $this->assertInstanceOf(ShipmentResult::class, $result);
        $this->assertSame('630000491494', $result->waybillNumber);
        $this->assertSame('pending', $result->status);
        $this->assertSame('OPEN2026289267907', $result->reference);
        $this->assertSame('93-C24-NS610', $result->meta()['sorting_code']);
        $this->assertSame('EC2', $result->meta()['third_sorting_code']);
        $this->assertSame('10.00', $result->meta()['package_charge_weight']);
    }

    public function test_map_from_inquiry_returns_shipment_result_from_get_orders_response(): void
    {
        $envelope = $this->fixture('get-shipment-success');

        $result = ShipmentMapper::mapFromInquiry($envelope['data'], 'YLTEST202404101519');

        $this->assertInstanceOf(ShipmentResult::class, $result);
        $this->assertSame('630002864925', $result->waybillNumber);
        $this->assertSame('unknown', $result->status);
        $this->assertSame('YLTEST202404101519', $result->reference);
        $this->assertSame('EZ', $result->meta()['expressType']);
        $this->assertSame('PP_PM', $result->meta()['payType']);
    }

    public function test_map_from_inquiry_falls_back_to_given_reference_when_missing_from_data(): void
    {
        $result = ShipmentMapper::mapFromInquiry(['billCode' => 'BC001'], 'FALLBACK-REF');

        $this->assertSame('FALLBACK-REF', $result->reference);
    }
}
