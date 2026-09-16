<?php

namespace Laraditz\Courier\JtExpress;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laraditz\Courier\Contracts\CourierDriver;
use Laraditz\Courier\Contracts\ExtractsWebhookReference;
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
use Laraditz\Courier\Enums\DeliveryMode;
use Laraditz\Courier\JtExpress\Events\TrackingUpdated;
use Laraditz\Courier\JtExpress\Http\JtExpressClient;
use Laraditz\Courier\JtExpress\Http\JtExpressSigner;
use Laraditz\Courier\JtExpress\Mappers\CancelMapper;
use Laraditz\Courier\JtExpress\Mappers\LabelMapper;
use Laraditz\Courier\JtExpress\Mappers\ShipmentMapper;
use Laraditz\Courier\JtExpress\Mappers\TrackingMapper;

class JtExpressDriver implements CourierDriver, HandlesWebhooks, ExtractsWebhookReference
{
    /**
     * Seconds of clock skew tolerated on the `timestamp` header before a push is
     * treated as a replay. Set `webhook_timestamp_tolerance` to 0 to disable.
     */
    private const DEFAULT_TIMESTAMP_TOLERANCE = 900;

    private JtExpressClient $client;
    private JtExpressSigner $signer;

    public function __construct(private readonly array $config, ?JtExpressClient $client = null)
    {
        $this->client = $client ?? new JtExpressClient($config);
        $this->signer = new JtExpressSigner($config['private_key'] ?? '');
    }

    public function createShipment(ShipmentPayload $payload): ShipmentResult
    {
        $reference = $payload->reference ?? (string) Str::uuid();

        $inner = $this->client->dispatch('order/addOrder', [
            'txlogisticId' => $reference,
            'actionType' => 'add',
            'serviceType' => '1',
            'payType' => 'PP_PM',
            'expressType' => $payload->serviceCode,
            'sender' => $this->formatAddress($payload->sender),
            'receiver' => $this->formatAddress($payload->recipient),
            'items' => [
                [
                    'itemName' => $payload->parcel->description,
                    'number' => (string) $payload->parcel->quantity,
                    'itemValue' => (string) $payload->parcel->declaredValue,
                    'weight' => (string) $payload->parcel->weight,
                ]
            ],
            'packageInfo' => [
                'packageQuantity' => (string) $payload->parcel->quantity,
                'weight' => (string) $payload->parcel->weight,
                'packageValue' => (string) $payload->parcel->declaredValue,
                'goodsType' => 'ITN8',
                'length' => (string) $payload->parcel->length,
                'width' => (string) $payload->parcel->width,
                'height' => (string) $payload->parcel->height,
            ],
            'remark' => $payload->remarks ?? '',
        ], reference: $reference);

        return ShipmentMapper::map($inner['data'], $reference);
    }

    public function getShipment(string $reference): ShipmentResult
    {
        $inner = $this->client->dispatch('order/getOrders', [
            'txlogisticId' => $reference,
        ], reference: $reference);

        return ShipmentMapper::mapFromInquiry($inner['data'], $reference);
    }

    public function track(string $trackingNumber): TrackingResult
    {
        try {
            $inner = $this->client->dispatch('logistics/trace', [
                'billCode' => $trackingNumber,
            ], waybillNumber: $trackingNumber);
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
        throw new \Laraditz\Courier\Exceptions\UnsupportedOperationException(
            'J&T Express Malaysia does not support rate quoting.'
        );
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
            'billCode' => $waybillNumber,
            'reason' => 'Cancelled via laraditz/courier',
        ], reference: $reference, waybillNumber: $waybillNumber);

        return CancelMapper::map($inner);
    }

    public function getLabel(string $waybillNumber, ?string $reference = null): LabelResult
    {
        if ($reference === null) {
            throw new \Laraditz\Courier\Exceptions\InvalidPayloadException(
                'J&T Express requires the original order reference to fetch a label.'
            );
        }

        $inner = $this->client->dispatch('order/printOrder', [
            'txlogisticId' => $reference,
            'billCode' => $waybillNumber,
        ], reference: $reference, waybillNumber: $waybillNumber);

        return LabelMapper::map($inner['data'], $waybillNumber);
    }

    public function getAvailability(AvailabilityPayload $payload): ServiceCollection
    {
        throw new \Laraditz\Courier\Exceptions\UnsupportedOperationException(
            'J&T Express Malaysia does not support service availability lookup.'
        );
    }

    public function getDeliveryModes(): array
    {
        return [DeliveryMode::Scheduled];
    }

    public function verifyWebhook(Request $request): bool
    {
        // The doc marks apiAccount mandatory, and checking it stops a callback signed
        // for another tenant being accepted here. Opt-in: we have not been able to
        // confirm that the value J&T pushes equals our own api_account (it is redacted
        // in courier_webhook_logs), and enabling it wrongly would reject every push.
        // Confirm against a live push first, then set webhook_verify_api_account=true.
        $expectedAccount = (string) ($this->config['api_account'] ?? '');

        if (($this->config['webhook_verify_api_account'] ?? false)
            && $expectedAccount !== ''
            && ! hash_equals($expectedAccount, (string) $request->header('apiAccount', ''))) {
            return false;
        }

        if (! $this->timestampIsFresh($request)) {
            return false;
        }

        $digest = $this->signer->digest((string) $request->input('bizContent', ''));

        return hash_equals($digest, (string) $request->header('digest', ''));
    }

    public function extractWebhookReference(Request $request): array
    {
        $order = $this->orders($request)[0] ?? [];

        return [
            'reference'     => $order['txlogisticId'] ?? null,
            'waybillNumber' => $order['billCode'] ?? null,
        ];
    }

    public function handleWebhook(Request $request): void
    {
        foreach ($this->orders($request) as $order) {
            foreach ($order['details'] ?? [] as $detail) {
                $scanTypeCode = (string) ($detail['scanTypeCode'] ?? '');

                event(new TrackingUpdated(
                    billCode: $order['billCode'] ?? '',
                    txlogisticId: $order['txlogisticId'] ?? null,
                    scanTypeCode: $scanTypeCode,
                    mappedStatus: TrackingMapper::mapStatus($scanTypeCode),
                    raw: $detail,
                ));
            }
        }
    }

    /**
     * Decodes bizContent into a list of orders.
     *
     * J&T Malaysia pushes a single order object, while the published example shows an
     * array of them. Both shapes are normalised here so callers see one list.
     *
     * @return array<int, array<string, mixed>>
     */
    private function orders(Request $request): array
    {
        $decoded = json_decode((string) $request->input('bizContent', '[]'), true);

        if (! is_array($decoded)) {
            return [];
        }

        $orders = array_is_list($decoded) ? $decoded : [$decoded];

        return array_values(array_filter($orders, 'is_array'));
    }

    /**
     * The `timestamp` header carries epoch milliseconds. Rejecting stale values
     * bounds how long a captured payload and digest stay replayable.
     */
    private function timestampIsFresh(Request $request): bool
    {
        $tolerance = (int) ($this->config['webhook_timestamp_tolerance'] ?? self::DEFAULT_TIMESTAMP_TOLERANCE);

        if ($tolerance <= 0) {
            return true;
        }

        $timestamp = (string) $request->header('timestamp', '');

        if ($timestamp === '' || ! ctype_digit($timestamp)) {
            return false;
        }

        return abs(microtime(true) - ((int) $timestamp / 1000)) <= $tolerance;
    }

    private function formatAddress(Address $address): array
    {
        return [
            'name' => $address->name,
            'phone' => $address->phone ?? '',
            'countryCode' => 'MYS',
            'address' => $address->line1,
            'postCode' => $address->postcode,
        ];
    }
}
