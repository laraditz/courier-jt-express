<?php

namespace Laraditz\Courier\JtExpress\Tests\Mappers;

use Laraditz\Courier\DTOs\Results\TrackingResult;
use Laraditz\Courier\Exceptions\ShipmentNotFoundException;
use Laraditz\Courier\JtExpress\Mappers\TrackingMapper;
use Laraditz\Courier\JtExpress\Tests\TestCase;

class TrackingMapperTest extends TestCase
{
    public function test_map_returns_tracking_result_with_mapped_statuses(): void
    {
        $envelope = $this->fixture('track-success');

        $result = TrackingMapper::map($envelope['data'], '630002864925');

        $this->assertInstanceOf(TrackingResult::class, $result);
        $this->assertSame('630002864925', $result->waybillNumber);
        $this->assertCount(3, $result->events);
        $this->assertSame('picked_up', $result->events[0]->status);
        $this->assertSame('out_for_delivery', $result->events[1]->status);
        $this->assertSame('unknown', $result->events[2]->status);
        $this->assertSame('An unrecognized scan type', $result->events[2]->description);
        $this->assertSame('unknown', $result->status, 'latest event (999) is unmapped so overall status falls back to unknown');
    }

    public function test_map_throws_shipment_not_found_when_data_empty(): void
    {
        $envelope = $this->fixture('track-not-found');

        $this->expectException(ShipmentNotFoundException::class);

        TrackingMapper::map($envelope['data'], 'UNKNOWN-BILL');
    }
}
