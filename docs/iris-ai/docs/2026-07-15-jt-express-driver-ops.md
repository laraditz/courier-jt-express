# Ops Log: J&T Express Malaysia Courier Driver

**Plan:** [docs/iris-ai/plans/2026-07-15-jt-express-driver-plan.md](../plans/2026-07-15-jt-express-driver-plan.md)
**Execution mode:** TDD (chosen at ops start, applied to every task)

## Group 1: Core Contract Foundation

**Repo:** laraditz/courier
**Branch:** feature/courier-shipment-reference-contract
**Status:** done — 6/6 tasks, 54/54 tests green

| Task | Commit | Result |
|---|---|---|
| 1.1 — Add `reference` to ShipmentPayload | c46dc99 | green |
| 1.2 — Add `reference` to ShipmentResult | e172812 | green |
| 1.3 — Update CourierDriver contract (add `getShipment()`, extend `cancelShipment`/`getLabel`) | a6549db | green (intentionally broke CourierFake + WebhookTest double, fixed in 1.4/1.5) |
| 1.4 — Restore CourierFake conformance | f75d956 | green |
| 1.5 — Restore WebhookTest anonymous double conformance | d16e1ca | green |
| 1.6 — Full suite verification | (no code change) | 54/54 green |

### Group 1 Deviation Log
No structural deviations — docs unchanged. Tasks 1.3–1.5 executed exactly as sequenced in the plan: the interface change in 1.3 was confirmed to fatally break both `CourierFake` and the `WebhookTest` anonymous double (as spec review had already flagged), and each was fixed in its own dedicated task per the plan.

## Group 2: Lalamove Compatibility

**Repo:** laraditz/courier-lalamove
**Branch:** feature/lalamove-get-shipment-compat
**Status:** done — 3/3 tasks, 64/64 tests green

| Task | Commit | Result |
|---|---|---|
| 2.1 — LalamoveDriver::getShipment() | 2747e7d | green |
| 2.2 — Update cancelShipment/getLabel signatures | (bundled into 2747e7d — PHP requires full interface conformance atomically) | green |
| 2.3 — Full suite verification | (no code change) | 64/64 green |

### Group 2 Deviation Log
No structural deviations. Task 2.2 had no isolated commit since the signature updates had to land in the same edit as 2.1 for the class to load at all — noted in the Task 2.1 report at the time.

## Group 3: SfExpress Compatibility

**Repo:** laraditz/courier-sfexpress
**Branch:** feature/sfexpress-get-shipment-compat
**Status:** done — 3/3 tasks, 38/38 tests green

| Task | Commit | Result |
|---|---|---|
| 3.1 — SfExpressDriver::getShipment() | adb1e63 | green |
| 3.2 — Update cancelShipment/getLabel signatures | (bundled into adb1e63, same reason as Group 2) | green |
| 3.3 — Full suite verification | (no code change) | 38/38 green |

### Group 3 Deviation Log
No structural deviations. Same bundled-commit note as Group 2.

## Group 4: JT Express Scaffolding & Client

**Repo:** laraditz/courier-jt-express
**Branch:** feature/jt-express-scaffolding
**Status:** done — 6/6 tasks, 9/9 tests green

| Task | Commit | Result |
|---|---|---|
| 4.1 — Package skeleton | b07fb78 | no test (pure scaffolding) |
| 4.2 — config/jtexpress.php | 9c9c58b | no test (static config) |
| 4.3 — JtExpressServiceProvider | dbb0c33 | green |
| 4.4 — JtExpressSigner | 8ee073b | green |
| 4.5 — JtExpressClient::dispatch() happy path | 6b0fbc8 | green |
| 4.6 — JtExpressClient error handling | 007d423 | green |

### Group 4 Deviation Log
No structural deviations. Note: this is the point the `courier-jt-express` repo itself was git-initialized (`main` → `develop` → `feature/jt-express-scaffolding`), since it had no `.git` directory yet. `.gitignore` added excluding `composer.lock` (matching `courier-sfexpress`'s library-package convention) and `.claude/settings.local.json`.

## Group 5: JT Express Driver, Mappers & Webhook

**Repo:** laraditz/courier-jt-express
**Branch:** feature/jt-express-driver
**Status:** done — 15/15 tasks, 38/38 tests green

| Task | Commit | Result |
|---|---|---|
| 5.1 — ShipmentMapper::map() | 4b4eb13 | green |
| 5.2 — Scaffold JtExpressDriver + createShipment() | 5ba1511 | green |
| 5.3 — ShipmentMapper::mapFromInquiry() | af20253 | green |
| 5.4 — JtExpressDriver::getShipment() | 93106cc | green |
| 5.5 — TrackingMapper::map() | efeb77a | green |
| 5.6 — JtExpressDriver::track() | 57f0799 | green |
| 5.7 — CancelMapper::map() | 386c3c6 | green |
| 5.8 — JtExpressDriver::cancelShipment() | a3c52a8 | green |
| 5.9 — LabelMapper::map() | c04e2ba | green |
| 5.10 — JtExpressDriver::getLabel() | 428ea02 | green |
| 5.11 — getRates()/getAvailability() | 11c3c96 | green |
| 5.12 — TrackingUpdated event | 93b8b2e | green |
| 5.13 — JtExpressDriver::verifyWebhook() | c6c285f | green |
| 5.14 — JtExpressDriver::handleWebhook() | 531d487 | green |
| 5.15 — Full suite verification | (no code change) | 38/38 green |

### Group 5 Deviation Log
- Extracted `TrackingMapper::mapStatus(string $scanTypeCode): string` as a public helper (Task 5.14) so `handleWebhook()` and `map()` (Task 5.5) share the same `$scanTypeMap` lookup instead of duplicating it — spec and Group 5 plan file updated to reflect this (commit c48ef29).

## Final State
All 5 groups done. All branches merged into `develop` in their respective repos (`laraditz/courier`, `laraditz/courier-lalamove`, `laraditz/courier-sfexpress`, `laraditz/courier-jt-express`). Full test suites green across all four repos at merge time.

**Known risk carried forward (per spec's Edge Cases section):** the digest/password signing algorithm (`JtExpressSigner`) and the `scanTypeCode` status map (`TrackingMapper`) are both derived from an auto-translated API doc and have not been verified against J&T's live sandbox. A manual smoke test using the sandbox credentials embedded in the docs is recommended before this driver is used against real traffic.
