<?php

namespace Laraditz\Courier\JtExpress\Mappers;

use Laraditz\Courier\DTOs\Results\LabelResult;
use Laraditz\Courier\Exceptions\LabelFetchException;

class LabelMapper
{
    public static function map(array $data, string $waybillNumber): LabelResult
    {
        $base64 = $data['base64EncodeContent'] ?? '';
        $url    = $data['urlContent'] ?? '';

        if ($base64 !== '') {
            return new LabelResult(
                waybillNumber: $waybillNumber,
                format: 'pdf',
                content: $base64,
                meta: ['txlogistic_id' => $data['txlogisticId'] ?? null],
            );
        }

        if ($url !== '') {
            return new LabelResult(
                waybillNumber: $waybillNumber,
                format: 'url',
                content: $url,
                meta: ['txlogistic_id' => $data['txlogisticId'] ?? null],
            );
        }

        throw new LabelFetchException(
            'J&T Express label response missing both base64EncodeContent and urlContent.'
        );
    }
}
