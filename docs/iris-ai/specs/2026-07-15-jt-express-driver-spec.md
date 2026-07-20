# Spec: J&T Express Malaysia Courier Driver

## Overview
Add a `getShipment()` operation and an optional order-reference channel to the shared `laraditz/courier` contract, propagate the (mostly no-op) changes through `courier-lalamove` and `courier-sfexpress`, then build `laraditz/courier-jt-express` — a new driver implementing the full contract against J&T Express Malaysia's Open Platform API (Create/Cancel/Print/Track/Inquiry order + tracking webhook).

## Codebase Context
- **Stack:** PHP 8.1+, Laravel package conventions, Orchestra Testbench + PHPUnit for tests, `illuminate/http` `Http` facade for all HTTP calls.
- **Contract:** `courier/src/Contracts/CourierDriver.php` defines the six operations every driver implements; `courier/src/Contracts/HandlesWebhooks.php` is an optional second interface for drivers with inbound webhooks (`verifyWebhook`, `handleWebhook`), wired automatically by `courier/src/Http/Controllers/WebhookController.php` via route `POST courier/webhook/{driver}` (`courier/routes/webhook.php`) — no route changes needed for J&T; its callback URL is `POST {app}/courier/webhook/jtexpress`.
- **DTOs:** All of `courier/src/DTOs/{Payloads,Results}/*.php` are `readonly` classes constructed exclusively with named arguments throughout the codebase (verified in every existing Mapper) — safe to append new nullable trailing properties without touching call sites.
- **Reference driver (`courier-sfexpress`):** the direct structural template — `src/Http/{SfExpressClient,SfExpressEncryptor}.php`, `src/Mappers/*.php` (one per DTO), `src/SfExpressDriver.php`, `src/SfExpressServiceProvider.php`, `config/sfexpress.php`, `tests/{Http,Mappers}/*Test.php` + `tests/fixtures/*.json`, `tests/TestCase.php` (Testbench bootstrap + config seeding + fixture loader). J&T's driver mirrors this 1:1, simplified because J&T has no OAuth/token-exchange step (SF does) — each request is self-signed via the `digest` header.
- **Reference driver (`courier-lalamove`):** shows the `HandlesWebhooks` pattern — `verifyWebhook()`/`handleWebhook()` on the driver, dispatching plain readonly event objects (`src/Events/OrderStatusChanged.php` etc.) via the global `event()` helper, matched on an event-type discriminator inside the payload.
- **Manager:** `courier/src/CourierManager.php` extends `Illuminate\Support\Manager`; each driver's service provider registers itself in `boot()` via `$app->make('courier')->extend('{name}', fn ($app, $config) => new {Driver}($config))`, reading config from `courier.drivers.{name}` (merged in `register()` via `mergeConfigFrom`).
- **Exceptions available (reused, no new exception classes needed):** `CourierException` (base), `AuthenticationException`, `CancellationException`, `InvalidPayloadException`, `LabelFetchException`, `RateFetchException`, `ShipmentCreationException`, `ShipmentNotFoundException`, `UnsupportedOperationException`.
- **All implementers of `CourierDriver`** (confirmed via a repo-wide search for `implements CourierDriver`, not just the two provider drivers): `courier-lalamove/src/LalamoveDriver.php`, `courier-sfexpress/src/SfExpressDriver.php`, `courier/src/Testing/CourierFake.php` (the fake used by consuming apps for `Courier::fake()`-style testing — constructs `ShipmentResult`/`CancelResult`/`LabelResult`/`TrackingResult` with positional args, has `assert*()` helpers), and an anonymous test double inside `courier/tests/WebhookTest.php::registerWebhookDriver()` (implements `CourierDriver, HandlesWebhooks` with the old method set, used by 4 webhook tests). Every one of these must gain `getShipment()` and the new `$reference` params or the interface change leaves them fatally non-conformant (PHP: "must implement the remaining abstract method").
- **Gaps this spec fills:** no digest/MD5 request-signing helper exists yet (SF's `SfExpressEncryptor` does AES+SHA256, not reusable); no driver yet demonstrates the "hardcode a courier-specific default with no DTO equivalent" pattern being extended twice more (`payType`, `serviceType`, cancel `reason`) — precedent is `SfExpressDriver::createShipment()` hardcoding `pickupType => 0` / `payMethod => '1'` inline.

## Chosen Implementation Approach
Single approach, already narrowed to specifics during brief clarification — no competing options remain at spec level beyond the tracking-status-mapping question resolved below. Structure: clone the `courier-sfexpress` package layout exactly for `courier-jt-express`; extend the core contract minimally (one new method, two new optional params) rather than introducing a parallel capability-interface, since the brief explicitly chose the direct-extension route for simplicity and consistency of DX across all three drivers.

**Tracking status mapping:** best-effort partial `scanTypeCode → status` map with an `'unknown'` fallback for unrecognized codes (mirrors `SfExpressDriver\Mappers\TrackingMapper::$opCodeMap`'s fallback pattern) — chosen over passing through J&T's raw `scanTypeCode` unmapped, because every other driver in this package family returns normalized status strings, and losing that consistency for J&T alone would surprise consumers who write status-handling code once against the shared `CourierDriver` contract. The map is necessarily incomplete because the source doc's `scanTypeCode`/`scanTypeName` table lost row alignment during translation — flagged as a documented risk (see Edge Cases) to refine against real sandbox tracking payloads during implementation.

## Functional Requirements

### Core package (`laraditz/courier`)
- **FR-01:** `CourierDriver` gains `getShipment(string $reference): ShipmentResult`.
- **FR-02:** `CourierDriver::cancelShipment()` signature becomes `cancelShipment(string $waybillNumber, ?string $reference = null): CancelResult`.
- **FR-03:** `CourierDriver::getLabel()` signature becomes `getLabel(string $waybillNumber, ?string $reference = null): LabelResult`.
- **FR-04:** `ShipmentPayload` gains a trailing constructor property `public ?string $reference = null`.
- **FR-05:** `ShipmentResult` gains a constructor property `public ?string $reference = null`, positioned after `estimatedDelivery` and before `meta`.
- **FR-05a:** `CourierFake::getShipment(string $reference): ShipmentResult` is added — returns `$this->responses['getShipment'] ?? new ShipmentResult('FAKE-001', 'pending', null)` and records the call in `$this->calls['getShipment']`, matching the existing pattern used by every other fake method.
- **FR-05b:** `CourierFake::cancelShipment()`/`getLabel()` signatures gain the new `?string $reference = null` param. The existing call-log shape is preserved unchanged (`$this->calls['cancelShipment'][] = $waybillNumber;`) so `assertCancelled(string $waybillNumber)`/`assertLabelFetched(string $waybillNumber)` keep working exactly as before — `$reference` is accepted for interface conformance but not logged.
- **FR-05c:** The anonymous `CourierDriver`/`HandlesWebhooks` test double in `courier/tests/WebhookTest.php::registerWebhookDriver()` gains a `getShipment(string $r): ShipmentResult { throw new \RuntimeException; }` method (matching the existing throw-on-unused-method style of its other stubbed methods) and updates `cancelShipment`/`getLabel` to accept the new optional `$reference` param.

### `courier-lalamove` (compatibility updates)
- **FR-06:** `LalamoveDriver::getShipment(string $reference): ShipmentResult` throws `UnsupportedOperationException`.
- **FR-07:** `LalamoveDriver::cancelShipment()`/`getLabel()` accept the new `?string $reference = null` param (unused — Lalamove has no equivalent concept).

### `courier-sfexpress` (compatibility updates)
- **FR-08:** `SfExpressDriver::getShipment(string $reference): ShipmentResult` throws `UnsupportedOperationException`.
- **FR-09:** `SfExpressDriver::cancelShipment()`/`getLabel()` accept the new `?string $reference = null` param (unused).

### `laraditz/courier-jt-express` (new package)
- **FR-10:** `JtExpressDriver::createShipment(ShipmentPayload $payload): ShipmentResult` calls `order/addOrder`. If `$payload->reference` is null, generates one via `(string) Str::uuid()`. Returns a `ShipmentResult` with `waybillNumber` = J&T's `billCode` and `reference` = the txlogisticId used (caller-supplied or generated).
- **FR-11:** `JtExpressDriver::getShipment(string $reference): ShipmentResult` calls `order/getOrders` keyed on `txlogisticId = $reference`.
- **FR-12:** `JtExpressDriver::track(string $trackingNumber): TrackingResult` calls `logistics/trace` keyed on `billCode = $trackingNumber`. Throws `ShipmentNotFoundException` when J&T reports no matching data.
- **FR-13:** `JtExpressDriver::cancelShipment(string $waybillNumber, ?string $reference = null): CancelResult` calls `order/cancelOrder`. Throws `InvalidPayloadException` if `$reference` is null (J&T requires `txlogisticId`).
- **FR-14:** `JtExpressDriver::getLabel(string $waybillNumber, ?string $reference = null): LabelResult` calls `order/printOrder`. Throws `InvalidPayloadException` if `$reference` is null.
- **FR-15:** `JtExpressDriver::getRates()` and `::getAvailability()` both throw `UnsupportedOperationException` immediately — no HTTP call, no mapper involved.
- **FR-16:** `JtExpressDriver implements HandlesWebhooks`. `verifyWebhook(Request $request): bool` recomputes the digest over the request's raw `bizContent` value and compares to the `digest` header via `hash_equals()`.
- **FR-17:** `handleWebhook(Request $request): void` decodes `bizContent` as JSON (array of `{billCode, txlogisticId, details[]}` objects) and dispatches one `Laraditz\Courier\JtExpress\Events\TrackingUpdated` event per entry in every `details[]` array.
- **FR-18:** All outbound requests are signed per `digest = base64_encode(md5($bizContentJson . $privateKey, true))` and sent as `POST` with `Content-Type: application/x-www-form-urlencoded`, body field `bizContent` (the JSON string), headers `apiAccount`, `digest`, `timestamp` (milliseconds).
- **FR-19:** The `password` business-parameter field is computed as `strtoupper(md5($plaintextPassword))` from the configured plaintext password on every request.
- **FR-20:** `JtExpressServiceProvider` registers the driver under `courier.drivers.jtexpress`, publishes `config/jtexpress.php`, and merges its default config — mirroring `SfExpressServiceProvider` exactly.

## Non-Functional Requirements
- **Security:** private key and plaintext password are read only from config (`env()`-backed), never logged; webhook signature is verified via `hash_equals()` (timing-safe), never `===`.
- **Reliability:** every client-level failure (HTTP failure, `code !== '1'` business error) throws a `CourierException` subclass — no silent failures; `track()` specifically narrows "not found" into `ShipmentNotFoundException` so callers can `catch` it distinctly, matching `SfExpressDriver::track()`'s existing pattern.
- **Testability:** zero live network calls in the test suite — `JtExpressClient` is fully mockable via constructor injection (same as `SfExpressClient`), and any test needing HTTP-level assertions uses `Http::fake()`.
- **No new runtime dependencies** beyond what `courier-sfexpress` already requires (no AES/encryption library needed — J&T's scheme is plain MD5/Base64, both native PHP).

## Data Model
No database tables. All new/changed shapes are in-memory DTOs and config arrays.

**`ShipmentPayload` (core, extended)**
| Property | Type | Status |
|---|---|---|
| sender, recipient, parcel, serviceCode, remarks, scheduledAt | existing | unchanged |
| `reference` | `?string` | **new** — trailing, defaults `null` |

**`ShipmentResult` (core, extended)**
| Property | Type | Status |
|---|---|---|
| waybillNumber, status, estimatedDelivery | existing | unchanged |
| `reference` | `?string` | **new** — inserted after `estimatedDelivery` |
| meta (private, accessed via `meta()`) | existing | unchanged |

**`config/jtexpress.php` (new)**
| Key | Source | Purpose |
|---|---|---|
| `api_account` | `env('JTEXPRESS_API_ACCOUNT')` | `apiAccount` header |
| `private_key` | `env('JTEXPRESS_PRIVATE_KEY')` | digest signing |
| `customer_code` | `env('JTEXPRESS_CUSTOMER_CODE')` | `customerCode` business param |
| `password` | `env('JTEXPRESS_PASSWORD')` | plaintext; hashed per-request |
| `sandbox` | `env('JTEXPRESS_SANDBOX', false)` | selects base URL |
| `base_url` | `'https://ylopenapi.jtexpress.my/webopenplatformapi/api'` | production |
| `sandbox_url` | `'https://demoopenapi.jtexpress.my/webopenplatformapi/api'` | sandbox |
| `timeout` | `30` | HTTP client timeout (seconds) |

## API Contracts

### `CourierDriver` (core, extended)
```php
interface CourierDriver
{
    public function createShipment(ShipmentPayload $payload): ShipmentResult;
    public function getShipment(string $reference): ShipmentResult;                          // new
    public function track(string $trackingNumber): TrackingResult;
    public function getRates(RatePayload $payload): RateCollection;
    public function cancelShipment(string $waybillNumber, ?string $reference = null): CancelResult; // extended
    public function getLabel(string $waybillNumber, ?string $reference = null): LabelResult;        // extended
    public function getAvailability(AvailabilityPayload $payload): ServiceCollection;
}
```

### J&T Express endpoints consumed (all new, external)
| Operation | Method | Path (appended to base) | Key business params |
|---|---|---|---|
| Create Order | POST | `order/addOrder` | `txlogisticId`, `actionType=add`, `serviceType`, `payType`, `expressType`, `sender`, `receiver`, `items[]`, `packageInfo` |
| Order Inquiry | POST | `order/getOrders` | `txlogisticId` |
| Cancel Order | POST | `order/cancelOrder` | `txlogisticId`, `billCode`, `reason` |
| Print AWB | POST | `order/printOrder` | `txlogisticId`, `billCode` |
| Tracking Inquiry | POST | `logistics/trace` | `txlogisticId` or `billCode` |
| Tracking Info Callback | POST (inbound) | app-registered URL → `courier/webhook/jtexpress` | `bizContent` = array of `{billCode, txlogisticId, details[]}` |

All requests: `Content-Type: application/x-www-form-urlencoded`, headers `apiAccount`, `digest`, `timestamp` (ms), body field `bizContent` (JSON string). Response envelope: `{ code: "1"|other, msg, data, requestId }` — `code !== "1"` is a business failure.

### `JtExpressClient` (new)
```php
class JtExpressClient
{
    public function __construct(array $config, ?JtExpressSigner $signer = null);
    public function dispatch(string $path, array $bizContent): array; // returns decoded envelope['data'] parent array (full envelope, mapper reads ['data'])
    public function customerCode(): string;
}
```

### `JtExpressSigner` (new)
```php
class JtExpressSigner
{
    public function __construct(string $privateKey);
    public function digest(string $bizContentJson): string;    // base64(md5(json + privateKey, raw))
    public function hashPassword(string $plaintext): string;   // strtoupper(md5(plaintext))
}
```

### `JtExpressDriver` (new) — request shaping
- **`formatAddress(Address $address): array`** — maps to J&T's `sender`/`receiver` shape: `name`, `phone`, `countryCode` (hardcoded `'MYS'`), `address` (`$address->line1`), `postCode`. `prov`/`city`/`area`/`email` intentionally omitted (J&T derives them from `postCode` for MYS parcels).
- **`packageInfo`** built from `$payload->parcel`: `packageQuantity` ← `quantity`, `weight` ← `weight`, `packageValue` ← `declaredValue`, `goodsType` ← hardcoded `'ITN8'`, `length`/`width`/`height` passed through.
- **`items`** — single-entry array: `itemName` ← `parcel->description`, `number` ← `quantity`, `itemValue` ← `declaredValue`, `weight` ← `weight`.
- **`payType`** hardcoded `'PP_PM'`; **`serviceType`** hardcoded `'1'`; **`expressType`** ← `$payload->serviceCode`.
- **`reason`** (cancelShipment only) hardcoded `'Cancelled via laraditz/courier'` — no DTO field carries a cancellation reason; flagged in Edge Cases as a future enhancement if a real reason needs to flow through.

### Mappers (new)
- **`ShipmentMapper::map(array $data, string $reference): ShipmentResult`** — from `addOrder` response: `waybillNumber` ← `billCode`, `status` ← `'pending'`, `estimatedDelivery` ← `null`, `reference` ← passed-in `$reference`, `meta` ← `['sorting_code' => data.sortingCode, 'third_sorting_code' => data.thirdSortingCode, 'package_charge_weight' => data.packageChargeWeight ?? null]`.
- **`ShipmentMapper::mapFromInquiry(array $data, string $reference): ShipmentResult`** — from `getOrders` response: `waybillNumber` ← `billCode`, `status` ← `'unknown'` (getOrders carries no delivery-progress field — only `track()` does), `estimatedDelivery` ← `null`, `reference` ← `data.txlogisticId ?? $reference`, `meta` ← raw `packageInfo`/`payType`/`expressType`/`createOrderTime` passthrough.
- **`TrackingMapper::mapStatus(string $scanTypeCode): string`** — public helper wrapping the `$scanTypeMap` lookup (`$scanTypeMap[$scanTypeCode] ?? 'unknown'`). Extracted during implementation so `handleWebhook()` (FR-17) can reuse the same status mapping instead of duplicating the lookup table.
- **`TrackingMapper::map(array $data, string $trackingNumber): TrackingResult`** — `$entry = $data[0] ?? []`; throws `ShipmentNotFoundException` if empty. Events built from `entry['details']`: `timestamp` ← `Carbon::parse(scanTime)`, `location` ← `scanNetworkName`, `description` ← `desc`, `status` ← `self::mapStatus(scanTypeCode)`. `TrackingResult::status` ← latest event's status.
  ```php
  private static array $scanTypeMap = [
      '10'  => 'picked_up',
      '20'  => 'dispatched',
      '30'  => 'arrived',
      '94'  => 'out_for_delivery',
      '100' => 'delivered',
      '110' => 'problem',
      '172' => 'returned',
      '173' => 'return_delivered',
      '300' => 'exception', '301' => 'exception', '302' => 'exception',
      '303' => 'exception', '304' => 'exception', '305' => 'exception', '306' => 'exception',
  ];
  ```
- **`CancelMapper::map(array $inner): CancelResult`** — reachable only after a successful dispatch (client already throws on `code !== '1'`), so `success` ← `true`; `message` ← `$inner['msg'] ?? 'Cancelled.'`; `meta` ← `['bill_code' => data.billCode ?? null, 'txlogistic_id' => data.txlogisticId ?? null]`.
- **`LabelMapper::map(array $data, string $waybillNumber): LabelResult`** — if `data.base64EncodeContent` non-empty: `format` = `'pdf'`, `content` = that value as-is (J&T already returns it base64-encoded — no download/re-encode step needed, unlike SF). Else if `data.urlContent` non-empty: `format` = `'url'`, `content` = that URL. Else throws `LabelFetchException`.
- **No `RateMapper`/`AvailabilityMapper` created** — `getRates()`/`getAvailability()` throw directly in the driver, no mapper call, avoiding dead stub classes.

### `TrackingUpdated` event (new)
```php
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
```

## Skills & Agents Available
- No project-specific skills apply beyond the standard IRIS workflow (this spec itself). Implementation should use `test-driven-development` discipline per task (write the failing test, then the mapper/client/driver code) — matches this codebase's existing test-first coverage style (one test file per class, fixtures over inline arrays for realistic payloads).
- `audit` agent: not needed pre-emptively, but worth a pass on `JtExpressSigner`/webhook signature verification specifically before merge, given it's new security-relevant code (timing-safe comparison, no secret leakage in exceptions).

## Edge Cases & Error Handling
- **Cancel/Label called without a reference:** `cancelShipment()`/`getLabel()` throw `InvalidPayloadException` when `$reference` is null — J&T's API rejects the call server-side otherwise (`txlogisticId is required`), so failing fast client-side gives a clearer error.
- **Digest/password algorithm risk:** derived from an auto-translated doc, not verified against a live sandbox call. Must be validated using the sandbox credentials embedded in the docs (`apiAccount 640826271705595946`, matching `privateKey`) before this is considered production-ready — flagged for a manual sandbox smoke test during/after implementation, outside the mocked unit-test suite.
- **`scanTypeCode` mapping is incomplete:** unrecognized codes map to `'unknown'` rather than guessing; description text (`desc`) is always preserved raw regardless of mapping, so no information is lost even when status normalization fails.
- **Track "not found":** any `CourierException` from `logistics/trace` (business error `999001030` or otherwise) is caught and rethrown as `ShipmentNotFoundException`, matching `SfExpressDriver::track()`.
- **`getShipment()` (Order Inquiry) has no status field:** returned `ShipmentResult::status` is always `'unknown'` — callers needing live delivery status should use `track()` instead; this is documented behavior, not a bug.
- **Print AWB multi-parcel responses:** `base64EncodeContent` is empty for multi-piece (multipleVotes) orders per the docs — but multipleVotes is out of scope for v1, so this path is only reachable if J&T's API still omits `base64EncodeContent` for single-parcel orders in practice; `LabelMapper` falls back to `urlContent` either way, so this degrades gracefully rather than failing.
- **Webhook replay/freshness:** `verifyWebhook()` only checks the digest signature, not timestamp freshness — replay protection is out of scope for v1 (J&T's docs don't specify a tolerance window); noted as a future hardening item.
- **Cancellation reason is hardcoded:** `'Cancelled via laraditz/courier'` is sent for every cancellation since no DTO field carries one. Acceptable for v1 per the "core shipment only" brief scope; would need a new optional param/DTO field if per-cancellation reasons become a requirement.

## Out of Scope
- International (non-MYS) shipments — customs info, prov/city/area address fields.
- COD, declared-value insurance (`offerFeeInfo`), multi-parcel (`multipleVotes`) orders.
- Rate quoting and service-availability lookups (no backing J&T endpoint — both throw `UnsupportedOperationException`).
- Per-cancellation custom reason text.
- Webhook replay/timestamp-freshness protection.
- Any queue jobs, persistence, or UI beyond the package's own driver/client/mapper/event code.
- Live integration testing against J&T's actual sandbox (unit tests are fully mocked; a manual sandbox smoke test is called out as a follow-up, not part of this implementation's automated test suite).
