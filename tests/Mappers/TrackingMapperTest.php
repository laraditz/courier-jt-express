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

    /**
     * Every scanTypeCode the published callback table lists must resolve to a real
     * status. Codes are taken verbatim from "7 Tracking Info Callback.md".
     */
    public function test_map_status_covers_every_documented_scan_type_code(): void
    {
        $documented = [
            '700', '701', '702', '703', '704',
            '400', '401', '402', '403', '404', '405',
            '10', '20', '30', '94', '100', '110', '172', '173', '200',
            '300', '301', '302', '303', '304', '305', '306',
        ];

        $unmapped = array_values(array_filter(
            $documented,
            fn (string $code) => TrackingMapper::mapStatus($code) === 'unknown'
        ));

        $this->assertSame([], $unmapped, 'Documented scanTypeCodes falling through to "unknown": ' . implode(', ', $unmapped));
    }

    public function test_map_status_returns_unknown_for_undocumented_code(): void
    {
        $this->assertSame('unknown', TrackingMapper::mapStatus('999'));
    }

    public function test_map_status_maps_customs_and_parcel_shop_codes(): void
    {
        $this->assertSame('customs_clearance', TrackingMapper::mapStatus('401'));
        $this->assertSame('customs_cleared', TrackingMapper::mapStatus('402'));
        $this->assertSame('at_parcel_shop', TrackingMapper::mapStatus('700'));
        $this->assertSame('in_transit', TrackingMapper::mapStatus('200'));
    }
}
