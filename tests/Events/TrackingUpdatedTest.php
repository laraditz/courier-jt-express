<?php

namespace Laraditz\Courier\JtExpress\Tests\Events;

use Laraditz\Courier\JtExpress\Events\TrackingUpdated;
use Laraditz\Courier\JtExpress\Tests\TestCase;

class TrackingUpdatedTest extends TestCase
{
    public function test_event_carries_expected_fields(): void
    {
        $event = new TrackingUpdated(
            billCode: 'BC001',
            txlogisticId: 'ORDER-001',
            scanTypeCode: '10',
            mappedStatus: 'picked_up',
            raw: ['scanTypeCode' => '10'],
        );

        $this->assertSame('BC001', $event->billCode);
        $this->assertSame('ORDER-001', $event->txlogisticId);
        $this->assertSame('10', $event->scanTypeCode);
        $this->assertSame('picked_up', $event->mappedStatus);
        $this->assertSame(['scanTypeCode' => '10'], $event->raw);
    }
}
