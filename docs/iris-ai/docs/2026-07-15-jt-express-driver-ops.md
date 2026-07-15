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
