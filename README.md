# laraditz/courier-jt-express

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laraditz/courier-jt-express.svg?style=flat-square)](https://packagist.org/packages/laraditz/courier-jt-express)
[![Total Downloads](https://img.shields.io/packagist/dt/laraditz/courier-jt-express.svg?style=flat-square)](https://packagist.org/packages/laraditz/courier-jt-express)
[![License](https://img.shields.io/packagist/l/laraditz/courier-jt-express.svg?style=flat-square)](./LICENSE.md)

J&T Express Malaysia driver for [laraditz/courier](https://github.com/laraditz/courier).

Targets the **J&T Express Malaysia Open Platform API** (`ylopenapi.jtexpress.my`) — Malaysia domestic shipping only.

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12, or 13
- `laraditz/courier` ^1.0

## Installation

```bash
composer require laraditz/courier-jt-express
```

Both service providers are auto-discovered. Publish the config:

```bash
php artisan vendor:publish --tag=courier-config
php artisan vendor:publish --tag=courier-jt-express-config
```

## Configuration

Add to your `.env`:

```env
COURIER_DRIVER=jtexpress

JTEXPRESS_API_ACCOUNT=your-api-account
JTEXPRESS_PRIVATE_KEY=your-private-key
JTEXPRESS_CUSTOMER_CODE=your-customer-code
JTEXPRESS_PASSWORD=your-plaintext-password
JTEXPRESS_SANDBOX=true
```

`config/jtexpress.php` (published separately):

```php
return [
    'api_account'   => env('JTEXPRESS_API_ACCOUNT'),
    'private_key'   => env('JTEXPRESS_PRIVATE_KEY'),
    'customer_code' => env('JTEXPRESS_CUSTOMER_CODE'),
    'password'      => env('JTEXPRESS_PASSWORD'),
    'sandbox'       => env('JTEXPRESS_SANDBOX', false),
    'base_url'      => 'https://ylopenapi.jtexpress.my/webopenplatformapi/api',
    'sandbox_url'   => 'https://demoopenapi.jtexpress.my/webopenplatformapi/api',
    'timeout'       => 30,
];
```

| Key | Description |
|---|---|
| `api_account` | Numeric account ID from the J&T Open Platform console — sent as the `apiAccount` header |
| `private_key` | Used to sign every request (see [Signing](#signing) below) |
| `customer_code` | Customer code provided by your J&T outlet, e.g. `J0086474299` |
| `password` | The **plaintext** password J&T issues per account. The driver hashes it (uppercase MD5) on every request — do not pre-hash it yourself |
| `sandbox` | `true` to use the demo environment, `false` for production |

## Available Methods

| Method | Parameters | Returns | Notes |
|---|---|---|---|
| `createShipment` | `ShipmentPayload $payload` | `ShipmentResult` | `order/addOrder`. Generates a UUID reference if `$payload->reference` is not supplied. |
| `getShipment` | `string $reference` | `ShipmentResult` | `order/getOrders`. `$result->status` is always `'unknown'` — this endpoint carries no delivery-progress field, use `track()` for live status. |
| `track` | `string $trackingNumber` | `TrackingResult` | `logistics/trace`. Throws `ShipmentNotFoundException` when the waybill is unknown. |
| `cancelShipment` | `string $waybillNumber, ?string $reference = null` | `CancelResult` | `order/cancelOrder`. Throws `InvalidPayloadException` if `$reference` is null — J&T requires it. |
| `getLabel` | `string $waybillNumber, ?string $reference = null` | `LabelResult` | `order/printOrder`. Throws `InvalidPayloadException` if `$reference` is null — J&T requires it. |
| `getRates` | `RatePayload $payload` | — | Throws `UnsupportedOperationException` |
| `getAvailability` | `AvailabilityPayload $payload` | — | Throws `UnsupportedOperationException` |

Rate quoting and service availability lookup are not supported by the J&T Express Malaysia API.

**Why `$reference` matters here:** J&T's cancel and print-label endpoints are keyed on your own order reference (`txlogisticId`), not the waybill number (`billCode`) — the opposite of most couriers. Persist `ShipmentResult::$reference` alongside your order when you create a shipment, and pass it back in to `cancelShipment()`/`getLabel()` later.

Refer to the [laraditz/courier README](https://github.com/laraditz/courier) for payload/result DTO definitions and full usage examples.

## Usage

```php
use Laraditz\Courier\Facades\Courier;
use Laraditz\Courier\DTOs\Shared\Address;
use Laraditz\Courier\DTOs\Shared\Parcel;
use Laraditz\Courier\DTOs\Payloads\ShipmentPayload;

$result = Courier::driver('jtexpress')->createShipment(new ShipmentPayload(
    sender: new Address(
        name: 'J&T sender',
        phone: '+60123456789',
        email: null,
        line1: 'No 32, Jalan Kempas 4',
        line2: null,
        line3: null,
        city: 'Bandar Penawar',
        state: 'Johor',
        postcode: '81930',
        country: 'MY',
    ),
    recipient: new Address(/* ... */),
    parcel: new Parcel(
        weight: 1.5,
        length: 20.0,
        width: 15.0,
        height: 10.0,
        declaredValue: 100.0,
        description: 'Goods',
        quantity: 1,
    ),
    serviceCode: 'EZ', // EX | EZ | FD | DO | JS
    reference: 'ORDER-001', // optional — a UUID is generated if omitted
));

$result->waybillNumber; // '630000491494' (J&T billCode)
$result->reference;     // 'ORDER-001' — persist this for cancelShipment()/getLabel() later

// Track
$tracking = Courier::driver('jtexpress')->track($result->waybillNumber);

// Cancel — requires the reference
Courier::driver('jtexpress')->cancelShipment($result->waybillNumber, $result->reference);

// Get label — requires the reference
$label = Courier::driver('jtexpress')->getLabel($result->waybillNumber, $result->reference);
$label->format;  // 'pdf' or 'url'
$label->content; // base64-encoded PDF bytes, or a direct download URL

// Look up an order by your own reference
$order = Courier::driver('jtexpress')->getShipment('ORDER-001');
```

## Webhooks

The driver implements `HandlesWebhooks`. `verifyWebhook()` recomputes the request digest from the incoming `bizContent` and compares it (via `hash_equals()`) against the `digest` header — the same signature scheme used for outbound requests, not a static secret token.

Give J&T your app's webhook URL:

```
POST {your-app}/courier/webhook/jtexpress
```

### Webhook events

| Event class | Fired when |
|---|---|
| `Laraditz\Courier\JtExpress\Events\TrackingUpdated` | Once per scan detail in an incoming Tracking Info Callback |

```php
public string  $billCode;
public ?string $txlogisticId;
public string  $scanTypeCode; // raw J&T code
public string  $mappedStatus; // normalised status (see table below)
public array   $raw;          // the raw scan detail
```

Listen for it in your `EventServiceProvider`:

```php
use Laraditz\Courier\JtExpress\Events\TrackingUpdated;

Event::listen(TrackingUpdated::class, function (TrackingUpdated $event) {
    // ...
});
```

## Signing

Every request is signed per the J&T Open Platform's scheme:

```
digest = base64_encode(md5($bizContentJson . $privateKey, true))
```

sent as the `digest` header alongside `apiAccount` and a millisecond `timestamp`. The `password` business parameter is `strtoupper(md5($plaintextPassword))`, computed fresh on every request from the plaintext password in your config.

> **Verify before production use.** This scheme is implemented from J&T's (partially machine-translated) API documentation and has not been confirmed against a live sandbox call. Test against the demo credentials in J&T's docs before sending real traffic.

## Scope

This driver covers Malaysia domestic shipments only, matching what the shared `Address`/`Parcel`/`ShipmentPayload` DTOs carry:

- `countryCode` is hardcoded to `MYS` for both sender and receiver
- `payType` is hardcoded to `PP_PM` (monthly account), `serviceType` to `1` (drop-off)
- `packageInfo.goodsType` is hardcoded to `ITN8` (packages, not documents)

**Not supported:** international shipments (customs info, province/city/area breakdown), COD, declared-value insurance, and multi-parcel (`multipleVotes`) orders — none of these exist on the shared DTOs today.

## Status Mapping

J&T `scanTypeCode` values mapped to the [normalized status vocabulary](https://github.com/laraditz/courier#normalized-status-vocabulary). This map is best-effort — J&T's own code/name reference table lost row alignment during translation, so only a handful of codes are confidently mapped; everything else falls back to `unknown` (the raw scan description is always preserved in `TrackingEvent::$description` regardless):

| scanTypeCode | Status |
|---|---|
| `10` | `picked_up` |
| `20` | `dispatched` |
| `30` | `arrived` |
| `94` | `out_for_delivery` |
| `100` | `delivered` |
| `110` | `problem` |
| `172` | `returned` |
| `173` | `return_delivered` |
| `300`–`306` | `exception` |
| other | `unknown` |

## Testing

```bash
composer test
```

## License

MIT
