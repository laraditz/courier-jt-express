<?php

namespace Laraditz\Courier\JtExpress;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laraditz\Courier\Contracts\CourierDriver;
use Laraditz\Courier\Contracts\ExtractsWebhookReference;
use Laraditz\Courier\Contracts\HandlesWebhooks;
use Laraditz\Courier\Contracts\ProvidesWebhookResponse;
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
use Laraditz\Courier\Enums\FulfillmentMode;
use Laraditz\Courier\JtExpress\Events\TrackingUpdated;
use Laraditz\Courier\JtExpress\Http\JtExpressClient;
use Laraditz\Courier\JtExpress\Http\JtExpressSigner;
use Laraditz\Courier\JtExpress\Mappers\CancelMapper;
use Laraditz\Courier\JtExpress\Mappers\LabelMapper;
use Laraditz\Courier\JtExpress\Mappers\ShipmentMapper;
use Laraditz\Courier\JtExpress\Mappers\TrackingMapper;
use Laraditz\Courier\JtExpress\Webhooks\WebhookRejection;
use Symfony\Component\HttpFoundation\Response;

class JtExpressDriver implements CourierDriver, HandlesWebhooks, ExtractsWebhookReference, ProvidesWebhookResponse
{
    /**
     * Seconds of clock skew tolerated on the `timestamp` header before a push is
     * treated as a replay. Set `webhook_timestamp_tolerance` to 0 to disable.
     */
    private const DEFAULT_TIMESTAMP_TOLERANCE = 900;

    /**
     * Why the current push failed verification, set by verifyWebhook() and read by
     * webhookRejectedResponse().
     *
     * Request-scoped: Manager::driver() caches this instance, and under Octane the
     * container outlives a request, so verifyWebhook() clears it on entry. Without
     * that, one push's rejection code would be reported for the next.
     */
    private ?WebhookRejection $rejection = null;

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
            'serviceType' => $this->serviceTypeFor($payload->fulfillment),
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
        ] + $this->collectionWindow($payload), reference: $reference);

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

    public function webhookAcceptedResponse(Request $request): Response
    {
        return $this->ack('1', 'success', 'SUCCESS', $request, 200);
    }

    public function webhookRejectedResponse(Request $request): Response
    {
        // Reads what verifyWebhook() recorded. It must not re-run any check: the
        // digest would be recomputed against a request already judged, and the two
        // code paths could drift into disagreeing about the same push.
        $rejection = $this->rejection;

        return $this->ack(
            $rejection?->code() ?? '0',
            $rejection?->message() ?? 'fail',
            'FAIL',
            $request,
            401,
        );
    }

    /**
     * J&T marks all four response fields mandatory and documents no error example,
     * so a rejection carries the same shape as an acknowledgement.
     *
     * The key is `requestID`, matching their example — their own field table spells
     * it `requestId`, and the two disagree within the same page.
     */
    private function ack(string $code, string $message, string $data, Request $request, int $status): Response
    {
        return response()->json([
            'code' => $code,
            'msg' => $message,
            'data' => $data,
            'requestID' => $this->requestId($request),
        ], $status);
    }

    /**
     * The courier_webhook_logs row this push was written to, so a requestID quoted
     * back by J&T support can be looked up directly.
     *
     * J&T sends no request id of their own and marks the field mandatory, so when
     * the log write failed and there is no row, a UUID keeps the response valid.
     * The two forms are distinguishable at a glance, and a UUID is itself the signal
     * that this push has no log row to find.
     */
    private function requestId(Request $request): string
    {
        $logId = $request->attributes->get('courier.webhook_log_id');

        return $logId !== null ? (string) $logId : (string) Str::uuid();
    }

    public function verifyWebhook(Request $request): bool
    {
        $this->rejection = null;

        // The doc marks apiAccount mandatory, and checking it stops a callback signed
        // for another tenant being accepted here. Opt-in: we have not been able to
        // confirm that the value J&T pushes equals our own api_account (it is redacted
        // in courier_webhook_logs), and enabling it wrongly would reject every push.
        // Confirm against a live push first, then set webhook_verify_api_account=true.
        $expectedAccount = (string) ($this->config['api_account'] ?? '');

        if (($this->config['webhook_verify_api_account'] ?? false) && $expectedAccount !== '') {
            $received = (string) $request->header('apiAccount', '');

            if ($received === '') {
                return $this->reject(WebhookRejection::ApiAccountEmpty);
            }

            if (! hash_equals($expectedAccount, $received)) {
                return $this->reject(WebhookRejection::ApiAccountMismatch);
            }
        }

        if ($staleness = $this->timestampProblem($request)) {
            return $this->reject($staleness);
        }

        $received = (string) $request->header('digest', '');

        if ($received === '') {
            return $this->reject(WebhookRejection::DigestEmpty);
        }

        $expected = $this->signer->digest((string) $request->input('bizContent', ''));

        if (! hash_equals($expected, $received)) {
            return $this->reject(WebhookRejection::DigestMismatch);
        }

        return true;
    }

    /**
     * Records why this push failed and rejects it.
     *
     * Always returns false, so each check in verifyWebhook() reads as a single
     * statement and no branch can record a reason without also rejecting.
     */
    private function reject(WebhookRejection $rejection): bool
    {
        $this->rejection = $rejection;

        return false;
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
    /**
     * What is wrong with the `timestamp` header, or null when it is acceptable.
     *
     * J&T codes an absent header separately from a malformed or stale one, so the
     * three cases this used to fold into a single bool are now distinguished.
     *
     * The `<= 0` guard is deliberate and not `=== 0`: a negative tolerance disables
     * the check, and narrowing it would start enforcing freshness on configurations
     * where it is currently off.
     */
    private function timestampProblem(Request $request): ?WebhookRejection
    {
        $tolerance = (int) ($this->config['webhook_timestamp_tolerance'] ?? self::DEFAULT_TIMESTAMP_TOLERANCE);

        if ($tolerance <= 0) {
            return null;
        }

        $timestamp = (string) $request->header('timestamp', '');

        if ($timestamp === '') {
            return WebhookRejection::TimestampEmpty;
        }

        if (! ctype_digit($timestamp)) {
            return WebhookRejection::TimestampInvalid;
        }

        return abs(microtime(true) - ((int) $timestamp / 1000)) <= $tolerance
            ? null
            : WebhookRejection::TimestampInvalid;
    }

    /**
     * The requested collection window, as J&T's addOrder expects it.
     *
     * Only meaningful for a pickup: there is nothing for J&T to schedule when the
     * sender is bringing the parcel in themselves, and sending a window for a
     * drop-off would describe a rider visit that is not going to happen.
     *
     * sendEndTime is optional in J&T's spec, so an open-ended window is sent as a
     * start alone rather than being rejected or silently completed.
     *
     * @return array<string, string>
     */
    private function collectionWindow(ShipmentPayload $payload): array
    {
        if ($payload->fulfillment !== FulfillmentMode::Pickup || $payload->scheduledAt === null) {
            return [];
        }

        $window = ['sendStartTime' => $payload->scheduledAt->format('Y-m-d H:i:s')];

        if ($payload->scheduledUntil !== null) {
            $window['sendEndTime'] = $payload->scheduledUntil->format('Y-m-d H:i:s');
        }

        return $window;
    }

    /**
     * Resolve the J&T serviceType for a fulfillment mode.
     *
     * A null mode falls back to the configured default rather than throwing: callers
     * written before ShipmentPayload carried a mode must keep booking successfully.
     * Consumers that care about the distinction are expected to set it explicitly —
     * J&T cannot be told after the fact which way a shipment was meant to be handed over.
     */
    private function serviceTypeFor(?FulfillmentMode $fulfillment): string
    {
        $default = (string) ($this->config['service_type_default'] ?? '1');

        if ($fulfillment === null) {
            return $default;
        }

        return (string) ($this->config['service_type_map'][$fulfillment->value] ?? $default);
    }

    /**
     * J&T's address shape, which is flatter than the shared Address DTO.
     *
     * Every street line is folded into `address`. J&T's only other street field is
     * addressBak, which it accepts and then discards — confirmed against the sandbox,
     * where order/getOrders never echoes it back — so anything not in `address` is
     * lost, and a missing unit or floor number is a failed delivery.
     *
     * prov/city are sent for completeness but are not load-bearing: J&T resolves the
     * administrative hierarchy from the postcode and overrides whatever it is given.
     *
     * @return array<string, string>
     */
    private function formatAddress(Address $address): array
    {
        $street = array_filter([
            $address->line1,
            $address->line2,
            $address->line3,
        ], static fn (?string $line): bool => $line !== null && trim($line) !== '');

        return [
            'name' => $address->name,
            'phone' => $address->phone ?? '',
            'countryCode' => 'MYS',
            'address' => implode(', ', $street),
            'city' => $address->city ?? '',
            'prov' => $address->state ?? '',
            'postCode' => $address->postcode,
        ];
    }
}
