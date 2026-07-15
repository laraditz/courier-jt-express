# Group 1: Core Contract Foundation

**Branch:** feature/courier-shipment-reference-contract
**Status:** done
**Parent plan:** 2026-07-15-jt-express-driver-plan.md
**Repo:** laraditz/courier

## Tasks

### Task 1.1 — Add `reference` to ShipmentPayload
- **What:** Add trailing `public ?string $reference = null` to `src/DTOs/Payloads/ShipmentPayload.php`
- **Test first:** `test_shipment_payload_reference_optional` + `test_shipment_payload_reference_can_be_set` in `tests/DTOs/PayloadTest.php`
- **Agent:** iris
- **Subagent:** no
- **Est:** 3 min

### Task 1.2 — Add `reference` to ShipmentResult
- **What:** Add `public ?string $reference = null` to `src/DTOs/Results/ShipmentResult.php`, positioned after `estimatedDelivery`, before `meta`
- **Test first:** `test_shipment_result_reference_optional` + `test_shipment_result_reference_can_be_set` in `tests/DTOs/ResultTest.php`
- **Agent:** iris
- **Subagent:** no
- **Est:** 3 min

### Task 1.3 — Update CourierDriver contract
- **What:** Add `getShipment(string $reference): ShipmentResult`; change `cancelShipment`/`getLabel` to accept trailing `?string $reference = null` in `src/Contracts/CourierDriver.php`
- **Test first:** none — a bare interface has no observable behavior; verified transitively by Tasks 1.4/1.5
- **Agent:** iris
- **Subagent:** no
- **Est:** 2 min

### Task 1.4 — Update CourierFake
- **What:** Implement `getShipment()` (records the call, returns fake/default `ShipmentResult`); update `cancelShipment`/`getLabel` signatures — call-log shape stays unchanged, `$reference` is accepted for interface conformance but not logged, in `src/Testing/CourierFake.php`
- **Test first:** `test_assert_get_shipment` (new) added to `tests/CourierFakeTest.php`; re-run existing `test_assert_cancelled`/`test_assert_label_fetched` to confirm no regression
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 1.5 — Update WebhookTest's anonymous driver double
- **What:** Add a `getShipment()` stub (`throw new \RuntimeException`) and update `cancelShipment`/`getLabel` signatures on the anonymous class in `tests/WebhookTest.php::registerWebhookDriver()`
- **Test first:** none — this class *is* test infrastructure; the 4 existing webhook tests are the verification
- **Agent:** iris
- **Subagent:** no
- **Est:** 2 min

### Task 1.6 — Full core suite green
- **What:** Run `laraditz/courier`'s full PHPUnit suite, confirm all green
- **Test first:** n/a (verification task)
- **Agent:** iris
- **Subagent:** no
- **Est:** 2 min
