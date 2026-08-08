# Chapter 19: Integration Testing Across Harbor Boundaries

## Educational question

**How should Harbor test that real application components collaborate correctly across HTTP, SQL, REST, SOAP, and presentation boundaries without depending on live external systems?**

Chapter 18 asked whether one useful behavior works in isolation. Chapter 19 asks whether selected real components agree. The core principle is:

> **An integration test should make selected boundaries real on purpose.**

## Learning objectives

By the end of this chapter, the learner can distinguish unit, integration, and end-to-end tests; choose real collaborators deliberately; reset a local relational database; exercise Harbor's HTTP stack; combine real clients, parsers, identity maps, and adapters; verify composition; preserve public contracts; follow failures across layers; and exclude the unpredictable internet.

## Banking concept

A financial application can have excellent unit tests and still fail when components disagree. A presenter may expect a different field, a repository may assume a schema column that is absent, a router may miss its controller, a SOAP namespace may change, or frontend expectations may drift from JSON output. Integration tests target these seams. They are especially valuable on failure paths: HTTP 200 with malformed JSON, a SOAP Fault, a timeout, or a syntactically valid but unsupported vendor status.

This is not production certification. Fictional data, local SQLite, and deterministic transports prove Harbor's collaboration and translation rules, not a vendor network's behavior.

## Engineering concept

Unit testing asks, “Does this behavior work in isolation?” Integration testing asks, “Do these real components agree?” Money arithmetic is a unit. Northstar adapter plus REST client plus JSON parser is component integration. PDO plus SQLite is technology integration. Request, router, controller, service, presenter, and response is HTTP integration. A service using several real adapters is cross-layer integration. A real browser journey is end-to-end and remains primarily for the later capstone.

| Test | Real | Replaced |
|---|---|---|
| `PreviewTransfer` unit | application service | Harbor ports |
| Northstar integration | adapter + REST client + JSON parser | external network |
| SQL integration | repository + SQLite engine | production database |
| HTTP integration | router + controller + service + presenter | real vendors |
| End-to-end | browser + backend stack | real vendors |

“Integration” never means “everything real.” A fake remains appropriate outside the intended scope. Mocking the collaboration under examination, however, would make the scope too narrow.

## Architecture

The [integration testing boundaries diagram](../diagrams/integration-testing-boundaries.md) shows four intentional scopes. Live vendors sit outside all of them. The integration taxonomy is:

1. **Component integration:** several real in-process classes, such as adapter, REST client, and decoder.
2. **Technology integration:** code uses real local technology, such as PDO with SQLite.
3. **HTTP integration:** routing, request parsing, controllers, presenters, and status/header behavior collaborate.
4. **Cross-layer integration:** application orchestration crosses real adapters and data access without necessarily adding a browser.
5. **End-to-end:** a complete user journey; Chapter 19 intentionally does not expand every case into this scope.

## Implementation

`IntegrationTestApplicationFactory` is the test composition root. It creates real Harbor application services and HTTP composition, fresh databases, and recording deterministic transports. It never reads production secrets. Vendor URLs use the reserved `.invalid` domain, and transports have no socket implementation.

### Deterministic database isolation

Every factory `database()` call opens a new in-memory SQLite connection, explicitly enables foreign keys, executes `database/schema.sql`, and loads `database/fixtures.sql`. Isolation therefore comes from deterministic rebuild rather than test order or cleanup. Tests execute real prepared statements for member accounts, activity aggregates, `LEFT JOIN` never-active behavior, the Chapter 10 `NOT EXISTS` query, and injection-shaped input.

### Vendor integration scopes

Northstar tests keep the identity map, `NorthstarRestClient`, JSON decoding, models, translator, and adapter real. Only the HTTP transport is deterministic and recording. Heritage similarly keeps envelope construction, namespace-aware XML parsing, model creation, identity mapping, and adapter translation real while replacing SOAP I/O. ClearVerify keeps its REST parser, mapping, adapter, and failure translation real. Success, malformed content, unsupported values, faults, timeout, and unavailability cross these boundaries.

Deterministic transports remain useful precisely because the test is real where it matters: request construction, decoding, semantic translation, failure classification, and orchestration. The internet is unpredictable and adds no useful assertion to those scopes.

### HTTP and cross-layer composition

HTTP tests call `Router::dispatch`; they do not call controllers directly. They cover member summary, financial overview, verification, transfer preview, validation, media type, malformed JSON, timeout, malformed SOAP, and review blocking. Separate application tests exercise financial overview and transfer preview through real clients and adapters without presentation, so a routing defect can be distinguished from orchestration failure.

### Shared contract fixtures

Files in `contracts/api/` are both backend promises and frontend expectations. PHP compares decoded response JSON explicitly with those files. Member Web reads the same artifacts and runs its TypeScript runtime parsers. Whitespace is insignificant; fields are not. Fixtures are never regenerated automatically.

When a fixture comparison fails, ask:

1. Was the change intentional?
2. Is it backward compatible?
3. Does the frontend expect the old field?
4. Did vendor terminology leak?
5. Did infrastructure accidentally reshape the contract?

### Failure propagation and minimization

A Northstar transport timeout becomes a client transport failure, an adapter-owned Harbor `TIMEOUT`, and finally HTTP `504 upstream_timeout`. Heritage malformed XML becomes `INVALID_EXTERNAL_RESPONSE` and HTTP `502 upstream_invalid_response`. ClearVerify `MANUAL_REVIEW` becomes Harbor `REVIEW_REQUIRED`, blocks preview, and produces provider-neutral API vocabulary.

Recording transports show that ClearVerify receives only its subject identifier—not memo or amount—and Heritage receives the required account identifier. Public contract tests reject vendor identifiers and secret-shaped values. Invalid content type and malformed input stop at the HTTP boundary; hostile XML is rejected with network entity loading disabled. HTML-like memo text remains JSON data for the frontend's text-safe rendering.

## Run the laboratory

```bash
composer test:integration
./bin/digital-banking-lab integration-test-inventory
./bin/digital-banking-lab integration-network-check
./bin/verify
```

`bin/verify` visibly runs the legacy/full PHP suite, focused PHP unit suite, PHP integration suite, Member Web typecheck, and Member Web tests. It contains no sleeps or retries.

## What to observe

- Each test can name what is real and what is replaced.
- A new database means fixture mutation cannot leak into the next test.
- Request recorders make minimization assertions possible without a server.
- Happy-path contract files fail loudly when a presenter reshapes JSON.
- Failure tests prove agreement between transport, parser, adapter, application, and HTTP mapper.
- Running a selected composition twice produces byte-identical output.
- `integration-network-check` validates laboratory configuration; it does **not** claim a process-wide network sandbox.

## Engineering tradeoffs

Integration tests are slower and diagnose a broader scope than unit tests, so Harbor does not repeat every arithmetic or validation permutation. Real local SQLite detects executable SQL mistakes but is not proof of production-database parity. Fixture transports cannot reveal TLS, proxy, latency, or vendor deployment defects. Browser layout and complete user journeys remain end-to-end concerns. The suite favors small explicit scopes, deterministic control, and useful failure localization over maximum realism.

## Automated tests

The integration runner covers factory completeness, database reset/foreign keys, accounts and activity facts, inactivity and never-active members, parameter binding, Northstar request/JSON/translation, Heritage envelope/XML/fault/XXE handling, ClearVerify statuses and failures, four public contract fixtures, HTTP validation and media types, cross-layer use cases, failure propagation, data minimization, leak checks, and repeated deterministic composition. Member Web tests validate every shared fixture it consumes. Chapter 18 and the preceding full suite remain separate commands so integration failures do not obscure unit regressions.

## Exercise

Design tests for a hypothetical **CardGuard card-lock feature**. Harbor exposes `POST /api/cards/{cardId}/lock-preview`; CardGuard uses REST JSON. Do not implement it.

1. What should be unit tested?
2. What should be integration tested?
3. Which components should be real together?
4. Where should the deterministic external boundary sit?
5. What contract fixture should frontend and backend share?
6. Which timeout, malformed JSON, unsupported status, and policy failures need cross-layer tests?
7. What complete interaction should remain for end-to-end testing?

For every proposed test, write two labels: **real components** and **replaced boundary**. If the test cannot answer both, its scope is not yet explicit.
