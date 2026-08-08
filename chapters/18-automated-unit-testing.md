# Chapter 18: Automated Unit Testing as an Engineering Discipline

## Educational question

**How should Harbor design unit tests so each test verifies one meaningful engineering boundary without depending on unrelated infrastructure?**

The governing principle is: **a unit test should isolate the behavior under study, not isolate every class from every other class.** `Money::subtract` is a useful unit by itself. `PreviewTransfer` plus fake Harbor gateways is a useful unit. The Northstar adapter plus a fake Northstar client is another. A controller surrounded by mocks for a router, request, factory, adapter, HTTP client, parser, presenter, and logger usually is not.

## Learning objectives

After this chapter, a learner can identify a useful unit; distinguish unit, contract, integration, and end-to-end tests; select a test double; test domain values without infrastructure; test services through Harbor ports; test adapters without transport; test presenters, parsers, validators, and reducers as pure transformations; recognize brittle interaction tests; and explain the maintenance value of determinism.

## Banking concept

Small errors in money arithmetic, external-status translation, cutoff boundaries, available-balance comparisons, and browser request ordering have meaningful consequences. Fast tests make those rules cheap to exercise at exact boundaries. They do not make banking software safe by themselves: integration, end-to-end, security, operational, and human review remain necessary layers.

## Engineering concept

Test behavior at the **narrowest useful boundary**:

| Behavior | Unit and replacement |
|---|---|
| Money arithmetic | real `Money`; no double |
| Transfer orchestration | `PreviewTransfer`; fake Harbor ports |
| Vendor semantic translation | adapter; fake vendor client |
| REST request/response parsing | REST client; fake HTTP transport |
| Public routing and serialization | integration test, not an over-mocked controller test |
| Member Web state transition | pure reducer, or coordinator with manually controlled promises |

A **unit test** checks one meaningful behavior with infrastructure absent or replaced. A **contract test** proves an implementation meets a Harbor-owned interface expectation. An **integration test** exercises real collaboration or a real local technology boundary. An **end-to-end test** traverses a complete user/system path. Chapter 18 emphasizes the first; Chapter 19 will emphasize the third.

Many systems benefit from many fast unit tests, fewer integration tests, and a small end-to-end layer. That “test pyramid” is a heuristic, not a universal law; the appropriate mix follows system risks and shape.

## Architecture

The [unit-boundary diagram](../diagrams/unit-testing-boundaries.md) makes four independent units visible. Real HTTP, SQL, and the full API deliberately remain outside them.

### Fake, stub, spy, and mock

- A **fake** is a lightweight working implementation.
- A **stub** returns predefined values.
- A **spy** records interactions for later assertions.
- A **mock** is commonly pre-programmed with interaction expectations.

Everyday usage overlaps. Harbor favors small handwritten fakes and recording fakes because they expose its ports, remain deterministic, avoid framework coupling, and produce understandable failures. Mocking frameworks are not inherently bad.

| Need | Best first choice |
|---|---|
| Return a fixed Harbor result | stub/fake |
| Simulate an external vendor model | fake vendor client |
| Verify minimal data passed | recording fake/spy |
| Verify pure value logic | no double |
| Test SQL | real local test database |
| Test HTTP serialization | HTTP integration test |

## Implementation

`tests/Support/HarborFakes.php` contains focused fakes and a small member fixture builder—not a giant miscellaneous factory. The recording balance fake records only `AccountId`, making data minimization observable. Test data uses 238575 available, 50000 requested, and 188575 projected so an arithmetic error is obvious.

The polished exemplars demonstrate different boundaries:

1. domain: direct Money subtraction;
2. application: `PreviewTransfer` with digital-banking, balance, and verification fakes;
3. adapter: DDA becomes Harbor CHECKING;
4. failure: a fake client timeout becomes Harbor `IntegrationFailure`;
5. frontend pure unit: `parseUsdAmountToMinorUnits("12.34") === 1234` (the parser table includes this exact boundary);
6. frontend async unit: manually controlled promises prove a stale member completion cannot overwrite the current member.

Existing endpoint, SQL, REST-transport, and SOAP-transport tests remain valuable integration-style coverage. We did not manufacture controller mock tests that repeat it. Presenters remain best tested as pure Harbor-result-to-scalar transformations without an HTTP server.

### Avoid over-mocking

A brittle overview test might demand `getMember()` once, `getAccounts()` once, `getBalance()` exactly twice, and `present()` once. A harmless batching or caching refactor breaks it even when the correct overview is returned. Prefer an output assertion. Interaction assertions are justified when interaction *is* the requirement: ClearVerify must not receive a memo, balance lookup must stop after failure, or a stale response must not update state.

Arrange–Act–Assert is the consistent shape, but comments are unnecessary when names such as `it_maps_northstar_dda_to_harbor_checking` already communicate behavior.

## Run the laboratory

```bash
composer test:unit
./bin/digital-banking-lab test-inventory
./bin/digital-banking-lab test-determinism
cd apps/member-web && npm test
```

The inventory is an explicit stable teaching catalog rather than fragile source discovery. The determinism command documents executable repository conventions: fixed clock, sequence IDs, no live vendor calls or random fixtures in unit suites, no sleeps, and manually controlled frontend promises.

## What to observe

Ask for every test: What is the unit? Which collaborators are real? Which are replaced, and why? Does the assertion describe behavior or an implementation detail? Could a harmless refactor break it? Can it run without network, database, or browser? SQL belongs in repository integration tests; routing belongs in HTTP integration tests.

Frontend pure units accept values and return values: the USD parser needs no DOM; runtime DTO validators accept unknown JSON; the reducer accepts state plus event; the form validator accepts fields. Coordinator tests use a fake API and deferred promises—not `fetch`, real timers, or sleeps—to cover ordering, abort, stale completion, and Retry.

## Engineering tradeoffs

Fixture builders help only when they remove noisy setup; small domain-focused builders are preferable to an opaque `TestDataFactory`. A recording fake is noise when final output is enough. Controller unit tests are useful for unique parsing or response-mapping policy, but Harbor's existing HTTP tests already cover their collaboration more honestly.

Coverage can reveal unvisited code, but it cannot prove assertion quality. High coverage with poor assertions is weak; lower coverage centered on critical boundaries may be stronger. Use mutation thinking without a tool: if `>=` became `>` at an inactivity cutoff, would a boundary test fail? If DDA mapped to SAVINGS, would the adapter test fail? If `requested > available` became `requested >= available`, would an exact-balance test fail?

## Automated tests

The focused suite proves the three fakes implement Harbor ports, the recording fake receives only the source `AccountId`, fixture construction is deterministic, adapter success and failure translation work without HTTP, and both Chapter 18 command outputs are stable. The full legacy suite and frontend suite continue independently. No mocking framework, network access, random data, current clock, sleep, private-method assertion, or value-object mock was introduced.

## Exercise

Design tests—but do not implement the service—for Chapter 9's hypothetical `GetMemberLiquiditySummary`:

1. define the meaningful unit under test;
2. identify which Harbor ports to fake;
3. choose state/output assertions;
4. decide whether any interaction assertion expresses a real minimization or sequencing contract;
5. cover failure propagation;
6. cover exact monetary boundaries;
7. list SQL, HTTP, and composition behavior to leave for integration tests.

Would mocking PDO directly improve this application-service test? Why or why not? Explain where a real local database test would provide more useful confidence. Chapter 19 will test collaboration across Harbor APIs, SQL, REST and SOAP clients, and frontend/backend boundaries; do not turn this exercise into Chapter 19.
