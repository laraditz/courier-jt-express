<?php

namespace Laraditz\Courier\JtExpress\Mappers;

use Laraditz\Courier\DTOs\Results\CancelResult;

class CancelMapper
{
    public static function map(array $inner): CancelResult
    {
        $data = $inner['data'] ?? [];

        return new CancelResult(
            success: true,
            message: $inner['msg'] ?? 'Cancelled.',
            meta: [
                'bill_code'      => $data['billCode'] ?? null,
                'txlogistic_id'  => $data['txlogisticId'] ?? null,
            ],
        );
    }
}
