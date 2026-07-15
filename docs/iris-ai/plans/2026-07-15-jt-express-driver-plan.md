# Plan: J&T Express Malaysia Courier Driver

**Spec:** [docs/iris-ai/specs/2026-07-15-jt-express-driver-spec.md](../specs/2026-07-15-jt-express-driver-spec.md)
**Date:** 2026-07-15

## Groups

| # | Group | Branch | Status | File |
|---|---|---|---|---|
| 1 | Core Contract Foundation (`laraditz/courier`) | `feature/courier-shipment-reference-contract` | done | [g1](2026-07-15-jt-express-driver-plan-g1.md) |
| 2 | Lalamove Compatibility (`laraditz/courier-lalamove`) | `feature/lalamove-get-shipment-compat` | pending | [g2](2026-07-15-jt-express-driver-plan-g2.md) |
| 3 | SfExpress Compatibility (`laraditz/courier-sfexpress`) | `feature/sfexpress-get-shipment-compat` | pending | [g3](2026-07-15-jt-express-driver-plan-g3.md) |
| 4 | JT Express Scaffolding & Client (`laraditz/courier-jt-express`) | `feature/jt-express-scaffolding` | pending | [g4](2026-07-15-jt-express-driver-plan-g4.md) |
| 5 | JT Express Driver, Mappers & Webhook (`laraditz/courier-jt-express`) | `feature/jt-express-driver` | pending | [g5](2026-07-15-jt-express-driver-plan-g5.md) |

## Sequencing Notes

- **Group 1 is a Foundation group** — it changes `CourierDriver`, `ShipmentPayload`, `ShipmentResult`, `CourierFake`, and the `WebhookTest` test double in `laraditz/courier`. Groups 2, 3, and 4 all consume the updated contract and cannot start until Group 1 is merged.
- **Groups 2 and 3 are independent of each other** — both depend only on Group 1, can be branched and merged in either order or in parallel.
- **Group 5 depends on Group 4** — Group 4 builds the `JtExpressClient`/`JtExpressSigner`/service-provider/config scaffolding that every driver method and mapper in Group 5 calls into. Group 5 cannot start until Group 4 is merged.
- All four repos (`courier`, `courier-lalamove`, `courier-sfexpress`, `courier-jt-express`) are linked locally via Composer path repositories (`../courier`), so a Group 1 merge is immediately visible to Groups 2–5 without a package release step.
