# Changelog

All notable changes to `laraditz/courier-jt-express` will be documented in this file.

## Unreleased

### Fixed

- **Tracking callbacks produced no events.** J&T Malaysia pushes `bizContent` as a single order *object*, while their published example shows an *array* of them. `handleWebhook()` assumed the array and silently emitted zero `TrackingUpdated` events for every live push while still returning HTTP 200. Both shapes are now normalised. Confirmed against 7 recorded production pushes: 0 events before, 7 after.

### Added

- `verifyWebhook()` now rejects a push whose `timestamp` header is more than `webhook_timestamp_tolerance` seconds from now (default 900; set to `0` to disable), bounding how long a captured payload and digest stay replayable.
- `verifyWebhook()` can also require the `apiAccount` header to match the configured `api_account`, via `webhook_verify_api_account`. **Off by default** — confirm the value J&T sends before enabling, or every push will be rejected.
- The driver implements `ExtractsWebhookReference`, so `courier_webhook_logs` rows now carry `waybill_number`. `reference` stays null because J&T Malaysia does not send `txlogisticId` in callbacks.
- All 27 `scanTypeCode` values from J&T's callback reference are now mapped (previously 15). Adds `200`, `400`–`405` (customs) and `700`–`704` (parcel shop). The `700`–`704` readings are inferred from a table whose columns do not align — see the README caveat.
- Outbound API calls are now recorded in `courier_api_logs`: `JtExpressClient` routes every request through `laraditz/courier`'s `CourierHttpClient`, using the API path as the log `action`. `createShipment()` and `getShipment()` log against the order reference; `track()` logs against the waybill; `cancelShipment()` and `getLabel()` log against both.
- `JtExpressClient::dispatch()` accepts optional `$reference` and `$waybillNumber` arguments supplying that log context. Both default to `null` and are never sent to J&T, so existing callers are unaffected.
- `JtExpressClient` accepts an optional `CourierHttpClient` as its third constructor argument, for injecting a test double.

### Changed

- Requires a `laraditz/courier` providing `CourierHttpClient::asForm()` and `::timeout()` (v1.3.0). J&T's API is form-encoded, so the wrapper needs `asForm()` to preserve the wire format.

## v1.0.0

Initial release.

### Added

- `JtExpressDriver` implementing `laraditz/courier`'s driver contract for J&T Express Malaysia (`ylopenapi.jtexpress.my`).
- `createShipment()` — creates an order via `order/addOrder`, auto-generating a reference (UUID) when one isn't supplied.
- `getShipment()` — fetches order details via `order/getOrders`.
- `track()` — retrieves tracking history via `logistics/trace`, throwing `ShipmentNotFoundException` for unknown waybills.
- `cancelShipment()` — cancels an order via `order/cancelOrder`.
- `getLabel()` — retrieves a shipping label via `order/printOrder`.
- `getRates()` / `getAvailability()` — throw `UnsupportedOperationException`, as J&T's Malaysia API does not support rate quoting or service availability lookup.
- Webhook support via `HandlesWebhooks`: `verifyWebhook()` validates incoming callbacks by recomputing and comparing the request digest, and `handleWebhook()` dispatches a `TrackingUpdated` event per scan detail in a Tracking Info Callback.
- `JtExpressSigner` for request digest signing and password hashing per J&T's signing scheme.
- `config/jtexpress.php` with sandbox/production base URLs, credentials, and timeout settings.
- Auto-discovered `JtExpressServiceProvider` registering the `jtexpress` driver with `laraditz/courier`'s manager.
