<?php

namespace Laraditz\Courier\JtExpress\Events;

readonly class TrackingUpdated
{
    public function __construct(
        public string $billCode,
        public ?string $txlogisticId,
        public string $scanTypeCode,
        public string $mappedStatus,
        public array $raw,
    ) {}
}
