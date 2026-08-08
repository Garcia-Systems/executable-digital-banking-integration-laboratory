# Chapter 5: Consuming a Vendor REST API

## Educational question

**How should Harbor communicate with a vendor REST API without coupling its application and domain layers to HTTP or vendor-specific transport details?**

## Learning objectives

By the end of this chapter, you can distinguish an adapter from an HTTP client; construct an explicit vendor request; validate status, JSON, and required fields; classify transport, HTTP, decoding, and translation failures; test without a live network; preserve Harbor domain isolation; and trace vendor bytes into Harbor-owned values without putting HTTP status codes in `Member`.

## Banking concept

Financial institutions frequently depend on vendor-hosted HTTP APIs. “Show my accounts” may appear simple to a member, yet Harbor must identify its member, resolve a vendor customer key, call the platform, validate its answer, translate its terminology, construct Harbor values, and expose a Harbor-owned response. The member should not need to know that complexity exists.

The fictional Northstar contract is `GET /v1/customers/{customerKey}`. For `NS-CUST-4417`, it describes Avery Morgan and two products. No real institution, API, credential, DNS lookup, or member information participates.

## Engineering concept

Each boundary answers a different question:

- **HTTP transport:** “How do we communicate?”
- **Northstar model:** “What did the vendor say?”
- **Adapter translation:** “What does that mean to Harbor?”
- **Harbor application/domain model:** “What does Harbor do with it?”
- **Harbor API representation:** “What does Harbor expose to clients?”

The central translation is concrete:

```text
HTTP JSON:       "productClass": "DDA"
                         ↓
Northstar model: NorthstarProductClass("DDA")
                         ↓
Adapter:         maps DDA
                         ↓
Harbor domain:   AccountType::CHECKING
                         ↓
Harbor API:      "type": "checking"
```

JSON ends at the REST client. Typed Northstar objects cross into the adapter. Typed Harbor objects cross into the application. An explicit presenter alone controls public JSON.

## Architecture

See the [vendor REST integration diagram](../diagrams/vendor-rest-integration.md). The forward path is Harbor REST API → `GetMemberSummary` → `DigitalBankingGateway` → `NorthstarDigitalBankingAdapter` → `NorthstarClient` → `NorthstarRestClient` → `HttpClient` → Northstar. The reverse path is HTTP response → decoder → `NorthstarCustomer` → adapter → `Member` → presenter → Harbor JSON.

`DeterministicNorthstarClient` remains useful for isolated semantic adapter tests. `NorthstarRestClient` implements the same `NorthstarClient` contract for transport-boundary tests and application composition. This deliberate replaceability is why the adapter does not depend on the old concrete object client.

## Implementation

`HttpClient` has one request operation and returns only status, headers, and body. `DeterministicHttpClient` records method, URL, and headers and selects explicit fixture files. It has no network implementation and therefore cannot accidentally contact `https://northstar.invalid`.

`NorthstarRestClient` builds `GET https://northstar.invalid/v1/customers/NS-CUST-4417` with `Accept: application/json`. A `200` is strictly decoded with `JSON_THROW_ON_ERROR`; customer and product fields are checked for presence and primitive type before Northstar objects are built. A `404` becomes `NorthstarCustomerNotFound`; a `500` or unexpected status becomes `NorthstarHttpFailure` retaining its status at the vendor-client boundary. Neither becomes a Harbor HTTP response there.

Failures identify their origin:

- an immediate simulated timeout is `NorthstarTimeoutFailure`;
- deterministic connection refusal is `NorthstarUnavailableFailure`;
- a non-success HTTP result is `NorthstarHttpFailure` (with a specific not-found subtype);
- malformed or structurally incomplete JSON is `NorthstarResponseDecodingFailure`;
- a valid Northstar `MMA` product reaches the adapter and becomes `VendorTranslationException` because Harbor does not support its meaning;
- an unknown Harbor identity becomes the existing application-level member-not-found outcome.

These are not interchangeable. A network timeout has no response; HTTP 500 has a vendor response; malformed JSON violates the wire contract; unsupported `MMA` is valid vendor data with no Harbor mapping; member not found is an application outcome. Later work can decide safe member-facing policies without distorting these origins.

## Run the laboratory

Run the successful diagnostic:

```bash
./bin/digital-banking-lab vendor-rest member-0001
```

Run each deterministic failure (all return immediately):

```bash
./bin/digital-banking-lab vendor-rest member-0001 --scenario=vendor-timeout
./bin/digital-banking-lab vendor-rest member-0001 --scenario=vendor-unavailable
./bin/digital-banking-lab vendor-rest member-0001 --scenario=customer-not-found
./bin/digital-banking-lab vendor-rest member-0001 --scenario=vendor-error
./bin/digital-banking-lab vendor-rest member-0001 --scenario=malformed-json
./bin/digital-banking-lab vendor-rest member-0001 --scenario=incomplete-response
./bin/digital-banking-lab vendor-rest member-0001 --scenario=unsupported-product
```

Then start Harbor's local API and request its unchanged Chapter 4 contract:

```bash
php -S 127.0.0.1:8080 -t public
curl -i http://127.0.0.1:8080/api/members/member-0001
```

## What to observe

The diagnostic exposes laboratory internals: Harbor member, vendor customer, request path, status, decoded identity, translated name, and `$10,570.75`. Harbor's public response exposes none of the customer/product keys, URL, header, fixture transport, or REST client class. Changing the backing transport did not change `tests/Fixtures/api/member-0001.json`.

The timeout does not call `sleep()` or wait. Unavailable means a simulated connection refusal and is deliberately distinct. Malformed JSON never continues with defaults. Missing `customerStatus` is rejected even though the JSON syntax is valid. `MMA` passes transport decoding as vendor vocabulary and fails only when the adapter asks what it means to Harbor.

## Engineering tradeoffs

The small local abstraction teaches a realistic seam without adding a package or generic HTTP framework. A production adapter could implement `HttpClient` with Guzzle while keeping its objects confined to infrastructure. Fixture files are more reviewable than random conditional failures, though a larger suite might use builders to reduce repetition.

The REST client knows the Northstar URL, status contract, JSON schema, and DTO construction. The adapter knows Northstar-to-Harbor semantics. `Member` knows neither HTTP nor Northstar. `MemberSummaryPresenter` controls Harbor JSON. If Northstar moved from REST to another transport, the client/composition would change; the adapter, domain, application service, and public presenter should remain stable.

## Automated tests

```bash
php tests/run.php
```

Tests inspect request construction, typed decoding, integer balances, every failure class, immediate timeout behavior, adapter equivalence, semantic translation failure, and the unchanged Chapter 4 fixture. The fixture-only transport proves the test path has no live network capability, while all Chapters 0–4 tests remain in the same suite.

## Exercise

Add deterministic fixture support for **429 Too Many Requests**, but do not implement retrying or real waiting. Write tests first and answer:

1. Is 429 a transport failure or a semantic domain failure?
2. Should `NorthstarRestClient` understand it?
3. Should Harbor's `Member` understand it?
4. Should Harbor's public API expose Northstar's exact error?
5. Which response information might later help retry logic?
6. How can the behavior be tested without actually waiting?

Document your boundary decision and observable deterministic behavior. This exercise prepares the later failure-handling chapter; it intentionally does not provide the full solution.
