# Group 5: JT Express Driver, Mappers & Webhook

**Branch:** feature/jt-express-driver
**Status:** pending
**Parent plan:** 2026-07-15-jt-express-driver-plan.md
**Repo:** laraditz/courier-jt-express
**Depends on:** Group 4 (merged)

## Tasks

### Task 5.1 — ShipmentMapper::map()
- **What:** Map `addOrder` response → `ShipmentResult` (waybillNumber ← billCode, status = `'pending'`, reference passthrough, meta: sortingCode/thirdSortingCode/packageChargeWeight)
- **Test first:** `tests/Mappers/ShipmentMapperTest.php` + fixture `create-shipment-success.json`
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 5.2 — Scaffold JtExpressDriver + implement createShipment()
- **What:** Create `JtExpressDriver implements CourierDriver, HandlesWebhooks` with stub bodies (`throw new \RuntimeException('not implemented')`) for every method except `createShipment()`, which gets the real implementation: generate/pass through `reference` (`Str::uuid()` if `$payload->reference` is null), build `formatAddress()` (countryCode hardcoded `MYS`), `packageInfo` (goodsType hardcoded `ITN8`), `items` (single entry from `$payload->parcel`), hardcoded `payType = 'PP_PM'` / `serviceType = '1'`, `expressType` ← `$payload->serviceCode`, call `order/addOrder`, map via `ShipmentMapper::map()`
- **Test first:** `tests/JtExpressDriverTest.php::test_create_shipment_returns_shipment_result` + `test_create_shipment_generates_reference_when_none_given`, mocked `JtExpressClient`
- **Agent:** iris
- **Subagent:** no
- **Est:** 5 min

### Task 5.3 — ShipmentMapper::mapFromInquiry()
- **What:** Map `getOrders` response → `ShipmentResult` (status hardcoded `'unknown'` — no delivery-progress field in this response; reference ← `data.txlogisticId ?? $reference`)
- **Test first:** extend `tests/Mappers/ShipmentMapperTest.php` + fixture `get-shipment-success.json`
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 5.4 — JtExpressDriver::getShipment()
- **What:** Replace the stub — call `order/getOrders` keyed on `txlogisticId`, map via `ShipmentMapper::mapFromInquiry()`
- **Test first:** extend `tests/JtExpressDriverTest.php`
- **Agent:** iris
- **Subagent:** no
- **Est:** 3 min

### Task 5.5 — TrackingMapper::map()
- **What:** Map `logistics/trace` response → `TrackingResult`, including `$scanTypeMap` (`10`→picked_up, `20`→dispatched, `30`→arrived, `94`→out_for_delivery, `100`→delivered, `110`→problem, `172`→returned, `173`→return_delivered, `300`–`306`→exception) with `'unknown'` fallback for unrecognized codes; throws `ShipmentNotFoundException` when `data` is empty
- **Test first:** `tests/Mappers/TrackingMapperTest.php` + fixtures `track-success.json`, `track-not-found.json`
- **Agent:** iris
- **Subagent:** no
- **Est:** 5 min

### Task 5.6 — JtExpressDriver::track()
- **What:** Replace the stub — call `logistics/trace` keyed on `billCode`, catch `CourierException` and rethrow as `ShipmentNotFoundException`, map via `TrackingMapper::map()`
- **Test first:** extend `tests/JtExpressDriverTest.php` (found + not-found cases)
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 5.7 — CancelMapper::map()
- **What:** Map a successful cancel response → `CancelResult` (`success = true` — client already throws on business failure before this is reached, `message` ← `$inner['msg'] ?? 'Cancelled.'`, meta: billCode/txlogisticId)
- **Test first:** `tests/Mappers/CancelMapperTest.php` + fixture `cancel-success.json`
- **Agent:** iris
- **Subagent:** no
- **Est:** 3 min

### Task 5.8 — JtExpressDriver::cancelShipment()
- **What:** Replace the stub — throw `InvalidPayloadException` if `$reference` is null; else call `order/cancelOrder` with `txlogisticId`, `billCode`, hardcoded `reason = 'Cancelled via laraditz/courier'`, map via `CancelMapper::map()`
- **Test first:** extend `tests/JtExpressDriverTest.php` (success + missing-reference cases)
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 5.9 — LabelMapper::map()
- **What:** If `base64EncodeContent` non-empty → format `'pdf'`, content = value as-is; else if `urlContent` non-empty → format `'url'`, content = URL; else throw `LabelFetchException`
- **Test first:** `tests/Mappers/LabelMapperTest.php` + fixtures `get-label-success-base64.json`, `get-label-success-url.json`
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 5.10 — JtExpressDriver::getLabel()
- **What:** Replace the stub — throw `InvalidPayloadException` if `$reference` is null; else call `order/printOrder` with `txlogisticId`/`billCode`, map via `LabelMapper::map()`
- **Test first:** extend `tests/JtExpressDriverTest.php` (success + missing-reference cases)
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 5.11 — getRates()/getAvailability()
- **What:** Replace both stubs — throw `UnsupportedOperationException` immediately, no HTTP call, no mapper
- **Test first:** extend `tests/JtExpressDriverTest.php`
- **Agent:** iris
- **Subagent:** no
- **Est:** 3 min

### Task 5.12 — TrackingUpdated event
- **What:** Create `src/Events/TrackingUpdated.php` (readonly: `billCode`, `txlogisticId`, `scanTypeCode`, `mappedStatus`, `raw`)
- **Test first:** simple constructor-assignment test
- **Agent:** iris
- **Subagent:** no
- **Est:** 2 min

### Task 5.13 — JtExpressDriver::verifyWebhook()
- **What:** Replace the stub — recompute the digest over the incoming `bizContent`, compare to the `digest` header via `hash_equals()`
- **Test first:** extend `tests/JtExpressDriverTest.php` (valid signature / invalid signature cases)
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 5.14 — JtExpressDriver::handleWebhook()
- **What:** Replace the stub — decode `bizContent` as JSON, dispatch one `TrackingUpdated` event per `details[]` entry per order in the payload. Extracts `TrackingMapper::mapStatus(string $scanTypeCode): string` as a public helper so this method and `track()` (Task 5.6) share the same status lookup instead of duplicating it.
- **Test first:** extend `tests/JtExpressDriverTest.php` with `Event::fake()`, assert dispatched event count and field values
- **Agent:** iris
- **Subagent:** no
- **Est:** 4 min

### Task 5.15 — Full jt-express suite green
- **What:** Run the full `laraditz/courier-jt-express` PHPUnit suite, confirm all green
- **Agent:** iris
- **Subagent:** no
- **Est:** 2 min
