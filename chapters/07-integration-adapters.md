# Chapter 7: Integration Adapters as a Reusable Pattern

## Educational question

What architectural pattern allows Harbor to integrate with many external systems without spreading vendor-specific logic across the application?

## Learning objectives

After this chapter, you can identify adapter responsibilities; distinguish a Harbor contract, adapter, vendor client, and transport; explain dependency inversion; compare REST/JSON and SOAP/XML integrations with common vocabulary; spot vendor leakage and false abstraction; add an adapter without changing a use case; locate identity, decoding, and semantic translation; and test adapters against their Harbor-owned contracts.

## Banking concept

A financial institution may use digital banking and core systems alongside identity-verification vendors, payment and card processors, CRM platforms, and marketing systems. We do **not** implement those additional systems here. Each can have different identifiers, terminology, transports, failure modes, and update cycles, while Harbor still needs coherent application behavior.

Northstar serves member lookup using customer/product language over REST/JSON. Heritage serves focused balance details using legacy account language over SOAP/XML. Their purposes remain different.

## Engineering concept

A **port** is a capability Harbor defines, such as retrieving a member or retrieving account balance details. An **adapter** connects that capability to a particular external system. A **vendor client** speaks in the vendor's model and owns request construction and response decoding. **Transport** moves bytes using HTTP or SOAP.

The reusable path is:

```text
Harbor-owned capability → vendor-specific adapter → vendor-specific client → transport
```

The adapter is between **Harbor meaning** and **external-system meaning**. The client is between the **external-system model** and **transport**. Thus the Northstar client parses JSON; the Heritage client parses XML; adapters map identities, statuses, product classes, and money into Harbor types.

### Dependency inversion

Vendor-dependent application code would be `GetMemberSummary → NorthstarDigitalBankingAdapter`. Harbor instead has:

```text
GetMemberSummary → DigitalBankingGateway ← NorthstarDigitalBankingAdapter
GetAccountBalanceDetails → AccountBalanceGateway ← HeritageCoreBankingAdapter
```

The arrow toward the Harbor contract matters: the application defines what it needs, while outer composition chooses the implementation. This is useful rather than ceremonial because a use case accepts a fake in a unit test, and Northstar's direct deterministic client can be exchanged for its REST-backed client without modifying `GetMemberSummary`.

### What should not be generalized

A `VendorGateway::perform(string $operation, array $data): array` would erase capabilities, identifiers, and results behind generic plumbing. A universal `ExternalSystemGateway` would falsely imply that member lookup, balances, and future payments have one business contract.

**Reuse the architectural pattern, not necessarily the exact interface.** Northstar and Heritage share an architectural pattern. They do not necessarily share a business interface.

### Adapter responsibility checklist

An adapter may:

1. receive Harbor-owned input;
2. resolve external identity;
3. invoke an external-system client;
4. translate external enums and statuses;
5. convert external monetary representations;
6. construct Harbor-owned results; and
7. reject unsupported external values.

It should not normally construct HTTP requests, parse JSON, construct SOAP envelopes, parse XML, route Harbor HTTP requests, format Harbor public JSON, or choose member-facing wording.

### Integration responsibility matrix

| Concern | Application | Adapter | Client | Transport |
|---|---:|---:|---:|---:|
| Harbor use case | Yes | No | No | No |
| Harbor domain types | Yes | Yes | No | No |
| Vendor terminology | No | Yes | Yes | Maybe protocol metadata |
| Identity translation | No | Yes | No | No |
| HTTP request | No | No | Yes | Sends it |
| JSON parsing | No | No | Yes | No |
| SOAP envelope | No | No | Yes | Sends it |
| XML parsing | No | No | Yes | No |

## Architecture

See the [integration adapter pattern diagram](../diagrams/integration-adapter-pattern.md). It shows the generic structure and its two deliberately distinct instances: `DigitalBankingGateway` with Northstar REST/JSON, and `AccountBalanceGateway` with Heritage SOAP/XML.

## Implementation

`IntegrationDescriptor` is immutable educational metadata: stable id, system, capability, port, adapter, client, transport, encoding, mapping requirement, result, and paths. `IntegrationCatalog` returns Northstar then Heritage deterministically. It is neither a service locator nor a dependency-injection container, and production behavior does not consult it.

`LaboratoryApplicationFactory` is the small explicit composition root. It wires each Harbor port to the appropriate adapter. `ArchitectureInspector` executes a deliberately small set of known source-boundary rules; it is not a general static-analysis framework.

Contract-oriented tests ask whether each adapter fulfills **its own port**, not whether unrelated adapters have identical methods. Northstar returns `Member` with Harbor identifiers and rejects unsupported product classes. Heritage returns `AccountBalanceDetails` with Harbor identity and rejects unsupported status or currency.

## Run the laboratory

```bash
./bin/digital-banking-lab integrations
./bin/digital-banking-lab integration northstar-digital-banking
./bin/digital-banking-lab integration heritage-core-banking
./bin/digital-banking-lab architecture-check
php tests/run.php
```

## What to observe

The catalog uses the same vocabulary to compare capabilities while retaining REST/JSON versus SOAP/XML. The detail command traces outbound and return paths. Architecture checks demonstrate that vendor models do not enter the domain, ports, or public presenter. The catalog describes wiring but never performs wiring.

`DDA → CHECKING` is semantic translation in the Northstar adapter/translator. JSON parsing belongs in `NorthstarRestClient`; XML parsing belongs in `HeritageSoapClient`. Identity maps are explicit adapter collaborators. Concrete choices live at the composition root.

## Engineering tradeoffs

Explicit ports add a small interface and wiring cost. That cost is justified at a real external boundary when it preserves typed Harbor meaning and testability. It would not justify generic arrays, a dependency-injection framework, formal folder ceremony, or a shared interface for unrelated capabilities. The source inspection checks are intentionally narrow and complemented by behavioral and reflection tests.

## Automated tests

The suite proves catalog ids, ordering, transports, encodings and results; typed transport-free port signatures; adapter identity and semantic behavior; fake-port testability; composition choices; domain/API isolation; client/transport placement; and all Chapters 0–6 regressions.

## Exercise

Design—but do not implement—an integration for the fictional **ClearVerify Identity Service**. Its contract is REST, uses `verificationSubjectId`, and returns `VERIFIED`, `REVIEW`, or `FAILED`.

Design only:

1. the Harbor-owned capability;
2. its Harbor-owned result type;
3. the vendor-specific adapter;
4. the vendor client boundary;
5. identity mapping responsibility;
6. transport location; and
7. status translation location.

Would Harbor call its interface `ClearVerifyGateway`, or name it for Harbor's need? Why? Show the dependencies and propose contract tests, but provide no implementation. This prepares the architecture for a later third-party fintech chapter without prematurely adding a vendor.
