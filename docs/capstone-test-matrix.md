# Capstone test matrix

“Capstone” means deterministic process-level composition, not a browser test. The visible browser journey remains a documented manual smoke test because the repository deliberately has no browser automation framework.

| Capability | Unit | Integration | Frontend | Capstone |
|---|---:|---:|---:|---:|
| Member summary | ✓ | ✓ | ✓ | ✓ |
| Financial overview | ✓ | ✓ | contract | ✓ |
| Activity profile | ✓ | ✓ | not exposed | ✓ |
| Verification | ✓ | ✓ | ✓ | ✓ |
| Transfer preview | ✓ | ✓ | ✓ | ✓ |
| Vendor failures | ✓ | ✓ | safe errors | ✓ |
| Security | focused | ✓ | ✓ | ✓ |
| Analytics | ✓ | ✓ | ✓ | ✓ |

The suites are `tests/Unit`, `tests/Integration`, `apps/member-web/tests`, and `tests/Capstone`. The root `./bin/verify` runs all of them plus regression, debugging, and delivery suites.
