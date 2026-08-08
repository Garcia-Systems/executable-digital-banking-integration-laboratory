# Chapter 0: The Digital Banking Integration Ecosystem

## Educational question

> What systems, actors, channels, and boundaries exist in a modern vendor-centered digital banking environment?

## Learning objectives

After this chapter, you can identify actors, channels, application and data systems; distinguish Harbor-, vendor-, and third-party ownership; trace an integration path; and explain why Harbor remains accountable for an integration it does not wholly own.

## Banking concept

Many financial institutions combine internally developed software with vendor-owned digital platforms, legacy systems, databases, and third-party fintech services. Harbor Community Credit Union is entirely fictional, as are every member and interface in this laboratory. Its architecture is one teaching fixture—not a claim that all institutions have the same design.

The **Member** is an actor who enters through Member Web or Mobile Banking. Authorized staff enter through Internal Operations. These channels depend on other systems to complete useful work.

## Engineering concept

An integration engineer frequently does not own every system involved in a feature. Their responsibility is to safely connect systems they control with systems they partially control or do not control.

These responsibilities are related but different:

- **System ownership** identifies who controls a system and its product lifecycle.
- **System responsibility** identifies who is accountable for a system's safe operation and outcomes in a particular context.
- **Integration responsibility** covers the contracts, validation, data handling, failure behavior, testing, and member experience where systems meet.

A vendor may own a platform while Harbor still owns responsibility for how Harbor integrates with it and what experience Harbor presents to its members. Ownership never removes Harbor's duty to protect member and financial data.

## Architecture

The deterministic fixture contains one actor, eight systems, three ownership types, six system categories, and seven directed relationships. View the [Mermaid ecosystem diagram](../diagrams/digital-banking-integration-ecosystem.md) for the boundaries and flows.

The Harbor Integration Layer is the controlled application boundary between Harbor's channels and its database, vendor platform, legacy core, and fintech provider. The model deliberately avoids authentication, queues, cloud infrastructure, and real vendor SDKs in this chapter.

## Implementation

Immutable PHP value objects model `Member`, `DigitalSystem`, and `IntegrationRelationship`. PHP enums constrain ownership and category vocabulary. `DigitalBankingEcosystem` validates relationship endpoints and performs a deterministic breadth-first path search. Infrastructure-free fixtures construct the same graph on every run, while the renderer supplies an observable text interface.

This is a teaching simplification. Production integrations require institution-specific security, governance, resiliency, auditability, vendor due diligence, and regulatory controls that are outside Chapter 0.

## Run the laboratory

PHP 8.2 or newer is the only runtime dependency for Chapter 0:

```bash
./bin/digital-banking-lab ecosystem
./bin/digital-banking-lab path
php tests/run.php
```

Composer is optional for this dependency-free increment; `composer test` invokes the same test runner when Composer is available.

## What to observe

- System listings are grouped by ownership rather than implying everything belongs to Harbor.
- Relationships state a protocol and purpose.
- Member Web reaches the vendor platform only through Harbor Integration Layer.
- The same command produces byte-for-byte identical output, enabling reliable experiments.
- The database, legacy core, and fintech provider are distinct destinations with distinct boundaries.

## Engineering tradeoffs

Readonly values and a fixed fixture favor clarity and repeatability over runtime configuration. A simple directed graph teaches boundary crossing without a graph library. String protocols are intentionally descriptive; later chapters can replace them with executable REST and SOAP adapter contracts. The fixture does not imply that vendor ownership determines data stewardship or operational accountability.

## Automated tests

The dependency-free runner checks expected system identity and order, ownership, relationship integrity, fixture determinism, the path across the Harbor boundary, and stable rendering. These are architecture assertions: they protect the meaning of the model, not merely executed lines.

## Exercise

1. Run `ecosystem`, save its output, run it again, and compare the two files with `cmp`.
2. Inspect the relationship from Harbor Integration Layer to Legacy Core Banking System. Identify its ownership boundary, interaction type, and purpose.
3. Add a fictional Harbor-owned notification channel and a relationship to the integration layer. Update the expected-system test first, then the fixture.
4. Explain in writing which party owns the new channel, which party is responsible for its member experience, and who owns each integration boundary. Do not introduce real member data or connect an external service.
