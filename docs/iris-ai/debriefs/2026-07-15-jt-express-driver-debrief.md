# Debrief: J&T Express Malaysia Courier Driver

`laraditz/courier-jt-express` exists now — a new driver package plugging J&T Express Malaysia's Open Platform API into the `laraditz/courier` abstraction, alongside `courier-lalamove` and `courier-sfexpress`. Getting there meant touching all four repos in the family, not just the new one.

## What Was Built

**`laraditz/courier` (core, extended)**
- `CourierDriver::getShipment(string $reference): ShipmentResult` — a new contract method for couriers with an order-inquiry endpoint
- `cancelShipment()`/`getLabel()` gained an optional trailing `?string $reference = null` param, for couriers (like J&T) whose cancel/label APIs key off the caller's own order reference rather than the waybill number
- `ShipmentPayload` gained `?string $reference` (input); `ShipmentResult` gained `?string $reference` (output) — so the reference flows end-to-end from creation through to later cancel/label/inquiry calls
- `CourierFake` and an anonymous test double inside `WebhookTest.php` — both direct implementers of `CourierDriver` that spec review flagged as fatal-error risks the moment the interface changed — were updated in lockstep

**`laraditz/courier-lalamove` and `laraditz/courier-sfexpress` (compatibility)**
- Both implement `getShipment()` by throwing `UnsupportedOperationException` (neither has an order-inquiry endpoint), and accept the new `$reference` param on `cancelShipment()`/`getLabel()` without using it

**`laraditz/courier-jt-express` (new package, the main deliverable)**
- `JtExpressDriver` implementing `CourierDriver` + `HandlesWebhooks`, structured exactly like `SfExpressDriver` (Http/Client, Mappers/, config, fully-mocked test suite with JSON fixtures)
- All five order operations wired to J&T's endpoints: `createShipment`→`addOrder`, `getShipment`→`getOrders`, `track`→`logistics/trace`, `cancelShipment`→`cancelOrder`, `getLabel`→`printOrder`
- `getRates()`/`getAvailability()` throw `UnsupportedOperationException` immediately — J&T MY has no backing endpoint for either
- Request signing (`JtExpressSigner`): `digest = base64(md5(bizContent_json + privateKey))`, password sent as uppercase MD5 hex of the plaintext password J&T issues
- Malaysia-domestic scope only for v1: `countryCode` hardcoded `MYS`, no COD/insurance/customs/multi-parcel support
- Webhook (Tracking Info Callback): `verifyWebhook()` recomputes the digest and compares via `hash_equals()`; `handleWebhook()` dispatches one `TrackingUpdated` event per scan detail

## Decisions Made

| Decision | Rationale |
|---|---|
| Extend the core `CourierDriver` contract rather than a parallel capability interface | Keeps a single, consistent DX across all drivers — caller doesn't need to type-check which interface a driver implements |
| `getRates`/`getAvailability` throw `UnsupportedOperationException` (not empty collections) | Fails loudly rather than letting "no data" be mistaken for "zero rates available" — matches the established convention already in the SfExpress test suite |
| `cancelShipment()`/`getLabel()` get an optional trailing `$reference` param, caller owns persisting it | J&T's cancel/print APIs require `txlogisticId` (mandatory) over `billCode` (optional) — the reference has to come from somewhere, and pushing that onto the caller avoids adding a persistence layer to the package |
| Core shipment fields only for v1 — no COD, insurance, customs, multi-parcel | None of these exist on the shared `Parcel`/`ShipmentPayload` DTOs; adding them was out of scope for this mission |
| `payType`/`serviceType` hardcoded in the driver, not configurable | Matches `SfExpressDriver`'s existing precedent of hardcoding its own equivalents rather than exposing config for values with no real per-request variability in this integration |
| Malaysia domestic only (`countryCode` hardcoded `MYS`) | International requires `customsInfo` and `prov`/`city`/`area` fields the shared DTOs don't carry — explicitly deferred |
| Best-effort `scanTypeCode`→status map with `'unknown'` fallback | The source doc's code/name table lost row alignment in translation; guessing wrong would be worse than an honest fallback, and raw `desc` text is always preserved regardless |
| Composer library convention: `composer.lock` gitignored in `courier-jt-express` | Matches `courier-sfexpress`'s convention (the more idiomatic choice for a Composer library vs. an application) |

## Deviations from Plan

- **`TrackingMapper::mapStatus()` extracted as a public helper** (Task 5.14): the plan described `TrackingMapper::map()` inlining the `$scanTypeCode` lookup. During `handleWebhook()` implementation, the same lookup was needed outside `map()`, so it was pulled into a public `mapStatus(string): string` method that both `map()` and `handleWebhook()` now call. Spec and the Group 5 plan file were updated to reflect this (commit `c48ef29`).
- **Tasks 2.2 and 3.2 (signature-only updates) had no separate commit** — PHP requires full interface conformance the moment a class declares it implements an interface, so the `getShipment()` addition and the `cancelShipment`/`getLabel` signature changes had to land in the same edit for Lalamove and SfExpress to even load. Both were bundled into the Task 2.1/3.1 commits, noted at the time in the between-task reports.
- **`courier-jt-express` had no `.git` directory going in** — it was initialized mid-mission (`main` → `develop` → `feature/jt-express-scaffolding`) at the start of Group 4, since the plan assumed an existing git-flow setup that didn't exist yet for this brand-new package.

## Test Coverage

194 tests total, all green, across four repos on `develop`:

| Repo | Tests |
|---|---|
| `laraditz/courier` | 54/54 |
| `laraditz/courier-lalamove` | 64/64 |
| `laraditz/courier-sfexpress` | 38/38 |
| `laraditz/courier-jt-express` | 38/38 |

Every functional requirement in the spec (FR-01 through FR-20, plus FR-05a/05b/05c added during spec review) has at least one corresponding test, written test-first (TDD, red confirmed before every implementation).

## Known Issues / Open Items

- **Digest/password signing algorithm is unverified against J&T's live sandbox.** It's implemented per the API intro doc's description (`base64(md5(bizContent + privateKey))`, uppercase MD5 for password), but the doc is auto-translated and the exact scheme was never confirmed against a real request. The docs embed sandbox test credentials (`apiAccount: 640826271705595946`) — a manual smoke test against `https://demoopenapi.jtexpress.my` before any production use is strongly recommended.
- **`scanTypeCode` status map is incomplete.** Only a handful of codes (`10`, `20`, `30`, `94`, `100`, `110`, `172`, `173`, `300`–`306`) are mapped with reasonable confidence; everything else falls back to `'unknown'`. Should be refined once real tracking payloads are observed.
- **International shipments, COD, insurance, and multi-parcel orders are entirely out of scope** for this driver as it stands — all deferred to a future phase, would each require new fields on the shared `Address`/`ShipmentPayload`/`Parcel` DTOs.
- **Cancellation reason is hardcoded** (`'Cancelled via laraditz/courier'`) — no DTO field exists to pass a real reason through.
- **Webhook replay/timestamp-freshness protection is not implemented** — `verifyWebhook()` only checks the digest signature.

## Next Steps

- Run a manual smoke test against J&T's sandbox (demo credentials are in `docs/j&t-api/`) to confirm the signing scheme is correct before any production traffic.
- If COD support becomes a real requirement, add `?float $codValue` to `ShipmentPayload` (core change) and wire it into `JtExpressDriver::createShipment()`'s `codInfo` block.
- If international shipping becomes a requirement, add `customsInfo` and `prov`/`city`/`area` fields to the shared `Address`/`ShipmentPayload` DTOs (core change, affects all drivers).
- Register J&T's Tracking Info Callback webhook URL (`POST {app}/courier/webhook/jtexpress`) with J&T once a consuming application is ready to receive live tracking events.
- All five feature branches (`feature/courier-shipment-reference-contract`, `feature/lalamove-get-shipment-compat`, `feature/sfexpress-get-shipment-compat`, `feature/jt-express-scaffolding`, `feature/jt-express-driver`) are already merged into their respective `develop` branches — none are outstanding.

## Docs Generated
- [Brief](../briefs/2026-07-15-jt-express-driver-brief.md)
- [Spec](../specs/2026-07-15-jt-express-driver-spec.md)
- [Plan](../plans/2026-07-15-jt-express-driver-plan.md) (+ 5 per-group files)
- [Implementation Notes](../docs/2026-07-15-jt-express-driver-ops.md)
- This debrief
