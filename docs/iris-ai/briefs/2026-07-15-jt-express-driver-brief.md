# Brief: J&T Express Malaysia Courier Driver

## Goal
Build `laraditz/courier-jt-express`, a new driver package that plugs J&T Express Malaysia's Open Platform API into the `laraditz/courier` abstraction, following the same structure as `laraditz/courier-sfexpress`.

## Context
`laraditz/courier` defines a `CourierDriver` contract implemented by provider-specific packages (`courier-lalamove`, `courier-sfexpress`). J&T Express Malaysia is the next courier to support. Source material is six Chinese-language API docs (auto-translated in places) covering: Create Order, Order Inquiry, Order Cancel, Print AWB, Tracking Inquiry, and Tracking Info Callback (webhook).

Two structural mismatches surfaced between J&T's API and the current contract, both requiring changes to the shared core package rather than workarounds local to this driver:

1. J&T's Order Inquiry endpoint has no slot in `CourierDriver` at all.
2. J&T's Cancel Order and Print AWB endpoints key off the caller's own order reference (`txlogisticId`, mandatory) rather than the courier's waybill number (`billCode`, optional) — but `cancelShipment()`/`getLabel()` only receive the waybill number.

Both are being fixed at the contract level so all three existing drivers stay consistent, rather than giving J&T a special-case workaround.

## Scope

### In
**`laraditz/courier` (core, modified)**
- New contract method `getShipment(string $reference): ShipmentResult`
- `ShipmentPayload` gains `?string $reference = null`
- `ShipmentResult` gains `?string $reference = null`
- `cancelShipment()` and `getLabel()` gain a new optional trailing `?string $reference = null` parameter

**`laraditz/courier-lalamove` (modified)**
- Implement `getShipment()` — throws `UnsupportedOperationException`
- Accept the new `$reference` param on `cancelShipment()`/`getLabel()` (unused)

**`laraditz/courier-sfexpress` (modified)**
- Implement `getShipment()` — throws `UnsupportedOperationException`
- Accept the new `$reference` param on `cancelShipment()`/`getLabel()` (unused)

**`laraditz/courier-jt-express` (new package — main deliverable)**
- `JtExpressDriver implements CourierDriver, HandlesWebhooks`, namespace `Laraditz\Courier\JtExpress`, package layout mirrors `courier-sfexpress` (`Http/Client`, `Mappers/`, `config/`, PHPUnit tests with mocked HTTP + JSON fixtures, no live network calls in tests)
- Endpoint mapping:
  - `createShipment` → `order/addOrder`
  - `track` → `logistics/trace`
  - `cancelShipment` → `order/cancelOrder`
  - `getLabel` → `order/printOrder`
  - `getShipment` → `order/getOrders`
  - `getRates`, `getAvailability` → throw `UnsupportedOperationException` (no J&T MY endpoint exists for either)
- Request signing: `digest = base64(md5(bizContent_json + privateKey))` (raw MD5 bytes, then base64), per the API intro doc. `password` sent as uppercase MD5 hex of the plaintext password J&T issues per customer account.
- Address/parcel mapping is Malaysia-domestic only: `countryCode` hardcoded to `MYS` for sender/receiver; `prov`/`city`/`area` omitted (J&T derives them from postcode for MYS parcels)
- No COD, insurance (`offerFeeInfo`), customs (`customsInfo`), or multi-parcel (`multipleVotes`) support in v1 — only fields already representable via `Address`/`Parcel`/`ShipmentPayload` are mapped
- `payType` hardcoded to `PP_PM` (monthly account); `serviceType` hardcoded to `1` (drop-off) — matching how `SfExpressDriver` hardcodes its own equivalents rather than exposing config
- `expressType` comes from `ShipmentPayload::$serviceCode` (e.g. `EZ`, `EX`, `FD`)
- `packageInfo.goodsType` defaults to `ITN8` (packages, as opposed to `ITN2` documents)
- Webhook (Tracking Info Callback): `verifyWebhook()` recomputes the digest over the incoming `bizContent` and compares via `hash_equals()` against the `digest` header; `handleWebhook()` dispatches one `Laraditz\Courier\JtExpress\Events\TrackingUpdated` event per scan detail in the payload, carrying `billCode`, `scanTypeCode`, a mapped internal status, and the raw scan detail

### Out
- International (non-MYS) shipments — deferred to a future phase; will require adding customs info and prov/city/area fields to the shared DTOs
- COD, declared-value insurance, multi-parcel (`multipleVotes`) shipments
- Any UI, queue jobs, or persistence beyond what the package itself needs to function
- Rate quoting and service availability (no backing J&T endpoint)

## Constraints
- Must follow the `courier-sfexpress` package's structure and testing conventions (mocked HTTP, JSON fixtures per endpoint, one PHPUnit test class per Client/Mapper/Driver)
- Composer package name: `laraditz/courier-jt-express`; path-repository dependency on `../courier` during local dev, matching sibling packages
- PHP `^8.1`, Laravel `illuminate/http` `^10.0|^11.0|^12.0|^13.0`, same as `courier-sfexpress`
- Source docs are machine-translated Chinese with some ambiguity (notably the exact password-hashing convention) — the digest/password scheme should be treated as best-effort from documentation and verified against the sandbox test credentials embedded in the docs (`apiAccount: 640826271705595946`, test `privateKey`) before considering the signing implementation done

## Open Questions Resolved
| Question | Answer |
|---|---|
| How should `getRates`/`getAvailability` behave with no backing J&T endpoint? | Throw `UnsupportedOperationException`, matching the established convention across drivers |
| Should J&T's Order Inquiry be exposed, and how? | Yes — added to the shared `CourierDriver` contract as `getShipment(string $reference): ShipmentResult`, implemented (as unsupported) in Lalamove and SfExpress too |
| Should this be one combined plan across all 4 repos, or the core change split into its own mission first? | One combined brief/spec/plan covering `courier`, `courier-lalamove`, `courier-sfexpress`, and `courier-jt-express` |
| How does `cancelShipment()`/`getLabel()` resolve J&T's mandatory `txlogisticId` when only given a waybill number? | Extend the core contract methods with a new optional trailing `?string $reference = null` param; caller is responsible for persisting and passing back the reference they received on `ShipmentResult` |
| Does `ShipmentPayload`/`ShipmentResult` need updating to carry the reference end-to-end? | Yes — `ShipmentPayload` gains `?string $reference = null` (input), `ShipmentResult` gains `?string $reference = null` (output) |
| Is COD / insurance / customs / multi-parcel support in scope for v1? | No — core shipment fields only (sender, recipient, weight/dims, declared value, description, quantity) |
| How are `payType`/`serviceType` (no equivalent DTO field) decided? | Hardcoded in the driver (`PP_PM` / `1`), matching `SfExpressDriver`'s precedent of hardcoding its own equivalents rather than adding config |
| Is international (non-MYS) shipping in scope for v1? | No — Malaysia domestic only; international is a later phase |
| How should the Tracking Info Callback webhook be verified and handled? | `verifyWebhook()` recomputes the digest and compares via `hash_equals()`; `handleWebhook()` dispatches one `TrackingUpdated` event per scan detail |
