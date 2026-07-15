<?php

namespace Laraditz\Courier\JtExpress;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laraditz\Courier\Contracts\CourierDriver;
use Laraditz\Courier\Contracts\HandlesWebhooks;
use Laraditz\Courier\DTOs\Payloads\AvailabilityPayload;
use Laraditz\Courier\DTOs\Payloads\RatePayload;
use Laraditz\Courier\DTOs\Payloads\ShipmentPayload;
use Laraditz\Courier\DTOs\Results\CancelResult;
use Laraditz\Courier\DTOs\Results\LabelResult;
use Laraditz\Courier\DTOs\Results\RateCollection;
use Laraditz\Courier\DTOs\Results\ServiceCollection;
use Laraditz\Courier\DTOs\Results\ShipmentResult;
use Laraditz\Courier\DTOs\Results\TrackingResult;
use Laraditz\Courier\DTOs\Shared\Address;
use Laraditz\Courier\JtExpress\Http\JtExpressClient;
use Laraditz\Courier\JtExpress\Mappers\CancelMapper;
use Laraditz\Courier\JtExpress\Mappers\ShipmentMapper;
use Laraditz\Courier\JtExpress\Mappers\TrackingMapper;

class JtExpressDriver implements CourierDriver, HandlesWebhooks
{
    private JtExpressClient $client;

    public function __construct(private readonly array $config, ?JtExpressClient $client = null)
    {
        $this->client = $client ?? new JtExpressClient($config);
    }

    public function createShipment(ShipmentPayload $payload): ShipmentResult
    {
        $reference = $payload->reference ?? (string) Str::uuid();

        $inner = $this->client->dispatch('order/addOrder', [
            'txlogisticId' => $reference,
            'actionType'   => 'add',
            'serviceType'  => '1',
            'payType'      => 'PP_PM',
            'expressType'  => $payload->serviceCode,
            'sender'       => $this->formatAddress($payload->sender),
            'receiver'     => $this->formatAddress($payload->recipient),
            'items'        => [[
                'itemName'  => $payload->parcel->description,
                'number'    => (string) $payload->parcel->quantity,
                'itemValue' => (string) $payload->parcel->declaredValue,
                'weight'    => (string) $payload->parcel->weight,
            ]],
            'packageInfo'  => [
                'packageQuantity' => (string) $payload->parcel->quantity,
                'weight'          => (string) $payload->parcel->weight,
                'packageValue'    => (string) $payload->parcel->declaredValue,
                'goodsType'       => 'ITN8',
                'length'          => (string) $payload->parcel->length,
                'width'           => (string) $payload->parcel->width,
                'height'          => (string) $payload->parcel->height,
            ],
            'remark'       => $payload->remarks ?? '',
        ]);

        return ShipmentMapper::map($inner['data'], $reference);
    }

    public function getShipment(string $reference): ShipmentResult
    {
        $inner = $this->client->dispatch('order/getOrders', [
            'txlogisticId' => $reference,
        ]);

        return ShipmentMapper::mapFromInquiry($inner['data'], $reference);
    }

    public function track(string $trackingNumber): TrackingResult
    {
        try {
            $inner = $this->client->dispatch('logistics/trace', [
                'billCode' => $trackingNumber,
            ]);
        } catch (\Laraditz\Courier\Exceptions\CourierException $e) {
            throw new \Laraditz\Courier\Exceptions\ShipmentNotFoundException(
                "Waybill [{$trackingNumber}] not found.",
                previous: $e
            );
        }

        return TrackingMapper::map($inner['data'], $trackingNumber);
    }

    public function getRates(RatePayload $payload): RateCollection
    {
        throw new \RuntimeException('not implemented');
    }

    public function cancelShipment(string $waybillNumber, ?string $reference = null): CancelResult
    {
        if ($reference === null) {
            throw new \Laraditz\Courier\Exceptions\InvalidPayloadException(
                'J&T Express requires the original order reference to cancel a shipment.'
            );
        }

        $inner = $this->client->dispatch('order/cancelOrder', [
            'txlogisticId' => $reference,
            'billCode'     => $waybillNumber,
            'reason'       => 'Cancelled via laraditz/courier',
        ]);

        return CancelMapper::map($inner);
    }

    public function getLabel(string $waybillNumber, ?string $reference = null): LabelResult
    {
        throw new \RuntimeException('not implemented');
    }

    public function getAvailability(AvailabilityPayload $payload): ServiceCollection
    {
        throw new \RuntimeException('not implemented');
    }

    public function verifyWebhook(Request $request): bool
    {
        throw new \RuntimeException('not implemented');
    }

    public function handleWebhook(Request $request): void
    {
        throw new \RuntimeException('not implemented');
    }

    private function formatAddress(Address $address): array
    {
        return [
            'name'      => $address->name,
            'phone'     => $address->phone ?? '',
            'countryCode' => 'MYS',
            'address'   => $address->line1,
            'postCode'  => $address->postcode,
        ];
    }
}
