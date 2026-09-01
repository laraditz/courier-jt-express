# Changelog

All notable changes to `laraditz/courier-jt-express` will be documented in this file.

## Unreleased

### Added

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
