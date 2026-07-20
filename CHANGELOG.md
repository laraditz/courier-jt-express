# Changelog

All notable changes to `laraditz/courier-jt-express` will be documented in this file.

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
