<?php

namespace Laraditz\Courier\JtExpress\Mappers;

use Carbon\Carbon;
use Laraditz\Courier\DTOs\Results\TrackingEvent;
use Laraditz\Courier\DTOs\Results\TrackingResult;
use Laraditz\Courier\Exceptions\ShipmentNotFoundException;

class TrackingMapper
{
    private static array $scanTypeMap = [
        '10'  => 'picked_up',
        '20'  => 'dispatched',
        '30'  => 'arrived',
        '94'  => 'out_for_delivery',
        '100' => 'delivered',
        '110' => 'problem',
        '172' => 'returned',
        '173' => 'return_delivered',
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
