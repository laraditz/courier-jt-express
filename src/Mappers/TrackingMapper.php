<?php

namespace Laraditz\Courier\JtExpress\Mappers;

use Carbon\Carbon;
use Laraditz\Courier\DTOs\Results\TrackingEvent;
use Laraditz\Courier\DTOs\Results\TrackingResult;
use Laraditz\Courier\Exceptions\ShipmentNotFoundException;

class TrackingMapper
{
    /**
     * Covers every scanTypeCode listed in the published callback table
     * (docs/j&t-api/7 Tracking Info Callback.md).
     *
     * Caveat on the 700-series: in the published table the scanTypeName and
     * scanType columns do not line up cleanly for those five codes (e.g. 702 is
     * named 驿站出库 / "parcel shop outbound" but typed signed_pop). These readings
     * follow scanTypeName. Confirm 702 and 704 with J&T before driving business
     * logic off them.
     */
    private static array $scanTypeMap = [
        // Parcel shop (驿站)
        '700' => 'at_parcel_shop',            // 驿站入库 — arrived at parcel shop
        '701' => 'pickup_reminder_sent',      // 取件通知 — collection notice sent
        '702' => 'collected_at_parcel_shop',  // 驿站出库 — released to recipient
        '703' => 'returning',                 // 异常出库 — exception outbound
        '704' => 'returning',                 // 取件交接 — return handover

        // Customs / cross-border
        '400' => 'in_transit',                // 清关提货 — picked up from cargo station
        '401' => 'customs_clearance',         // 清关中 — clearance in process
        '402' => 'customs_cleared',           // 清关放行 — released by customs
        '403' => 'in_transit',                // 清关交付 — handed over post-clearance
        '404' => 'in_transit',                // 大包入库 — bulk bag inbound
        '405' => 'in_transit',                // 中心入库 — hub inbound

        // Domestic line haul
        '10'  => 'picked_up',
        '20'  => 'dispatched',
        '30'  => 'arrived',
        '94'  => 'out_for_delivery',
        '100' => 'delivered',
        '110' => 'problem',
        '172' => 'returned',
        '173' => 'return_delivered',
        '200' => 'in_transit',                // 寄件入库 — sender inbound

        // Exception terminal states
        '300' => 'exception',
        '301' => 'exception',
        '302' => 'exception',
        '303' => 'exception',
        '304' => 'exception',
        '305' => 'exception',
        '306' => 'exception',
    ];

    public static function mapStatus(string $scanTypeCode): string
    {
        return self::$scanTypeMap[$scanTypeCode] ?? 'unknown';
    }

    public static function map(array $data, string $trackingNumber): TrackingResult
    {
        $entry = $data[0] ?? [];

        if (empty($entry)) {
            throw new ShipmentNotFoundException(
                "Waybill [{$trackingNumber}] not found."
            );
        }

        $events = array_map(
            fn (array $detail) => new TrackingEvent(
                timestamp: Carbon::parse($detail['scanTime']),
                location: $detail['scanNetworkName'] ?? '',
                description: $detail['desc'] ?? '',
                status: self::mapStatus($detail['scanTypeCode'] ?? ''),
            ),
            $entry['details'] ?? []
        );

        $latestStatus = !empty($events)
            ? $events[array_key_last($events)]->status
            : 'unknown';

        return new TrackingResult(
            waybillNumber: $entry['billCode'] ?? $trackingNumber,
            status: $latestStatus,
            estimatedDelivery: null,
            events: $events,
        );
    }
}
