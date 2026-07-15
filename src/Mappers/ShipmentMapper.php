<?php

namespace Laraditz\Courier\JtExpress\Mappers;

use Laraditz\Courier\DTOs\Results\ShipmentResult;

class ShipmentMapper
{
    public static function map(array $data, string $reference): ShipmentResult
    {
        return new ShipmentResult(
            waybillNumber: $data['billCode'],
            status: 'pending',
            estimatedDelivery: null,
            reference: $reference,
            meta: [
                'sorting_code'          => $data['sortingCode'] ?? null,
                'third_sorting_code'    => $data['thirdSortingCode'] ?? null,
                'package_charge_weight' => $data['packageChargeWeight'] ?? null,
            ],
        );
    }
}
