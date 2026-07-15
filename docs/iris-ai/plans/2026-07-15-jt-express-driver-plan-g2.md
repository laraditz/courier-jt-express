# Group 2: Lalamove Compatibility

**Branch:** feature/lalamove-get-shipment-compat
**Status:** done
**Parent plan:** 2026-07-15-jt-express-driver-plan.md
**Repo:** laraditz/courier-lalamove
**Depends on:** Group 1 (merged)

## Tasks

### Task 2.1 — LalamoveDriver::getShipment()
- **What:** Add `getShipment(string $reference): ShipmentResult` throwing `UnsupportedOperationException` in `src/LalamoveDriver.php`
- **Test first:** `test_get_shipment_throws_unsupported_operation_exception` in `tests/LalamoveDriverTest.php`
- **Agent:** iris
- **Subagent:** no
- **Est:** 3 min

### Task 2.2 — Update cancelShipment/getLabel signatures
- **What:** Accept trailing `?string $reference = null` on both methods (unused in the method body — Lalamove has no equivalent concept)
- **Test first:** re-run existing cancel/label tests in `tests/LalamoveDriverTest.php` to confirm no regression; no new assertions needed since behavior is unchanged
- **Agent:** iris
- **Subagent:** no
- **Est:** 3 min

### Task 2.3 — Full lalamove suite green
- **What:** Run `laraditz/courier-lalamove`'s full PHPUnit suite against the updated `../courier` path dependency
- **Agent:** iris
- **Subagent:** no
- **Est:** 1 min
