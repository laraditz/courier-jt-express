# Group 3: SfExpress Compatibility

**Branch:** feature/sfexpress-get-shipment-compat
**Status:** pending
**Parent plan:** 2026-07-15-jt-express-driver-plan.md
**Repo:** laraditz/courier-sfexpress
**Depends on:** Group 1 (merged)

## Tasks

### Task 3.1 — SfExpressDriver::getShipment()
- **What:** Add `getShipment(string $reference): ShipmentResult` throwing `UnsupportedOperationException` in `src/SfExpressDriver.php`
- **Test first:** `test_get_shipment_throws_unsupported_operation_exception` in `tests/SfExpressDriverTest.php`
- **Agent:** iris
- **Subagent:** no
- **Est:** 3 min

### Task 3.2 — Update cancelShipment/getLabel signatures
- **What:** Accept trailing `?string $reference = null` on both methods (unused in the method body)
- **Test first:** re-run existing cancel/label tests in `tests/SfExpressDriverTest.php` to confirm no regression
- **Agent:** iris
- **Subagent:** no
- **Est:** 3 min

### Task 3.3 — Full sfexpress suite green
- **What:** Run `laraditz/courier-sfexpress`'s full PHPUnit suite against the updated `../courier` path dependency
- **Agent:** iris
- **Subagent:** no
- **Est:** 1 min
