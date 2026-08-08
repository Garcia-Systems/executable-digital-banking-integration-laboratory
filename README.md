# Executable Digital Banking Integration Laboratory

An executable textbook about one central engineering question:

> **How do you safely extend a vendor-centered digital banking ecosystem with custom full-stack software?**

The learner is a full-stack integration engineer at **Harbor Community Credit Union**, an entirely fictional institution. Harbor uses existing vendor-owned banking platforms, a legacy core, internal software, and third-party fintech services. The laboratory extends that ecosystem; it does not attempt to rebuild a bank or connect to a real one.

## What this laboratory teaches

The book will grow a single architecture through PHP application and integration services, TypeScript clients, HTML/CSS experiences, SQL/MySQL-oriented data examples, simulated REST and SOAP boundaries, secure coding, automated tests, debugging, delivery practices, and evidence-driven experience improvements.

Each important concept should have a domain model, deterministic behavior, tests, an observable interface, an explanation, and an exercise. Production banking concerns are identified honestly but kept separate from teaching simplifications.

## Why deterministic?

Core exercises require no credentials, internet connection, live banking platform, or real member data. Fixed fictional fixtures and simulated services make runs repeatable, tests trustworthy, failures reproducible, and sensitive-data mistakes avoidable. The same input should yield byte-for-byte identical output.

## Current chapters

[Chapter 0: The Digital Banking Integration Ecosystem](chapters/00-digital-banking-integration-ecosystem.md) introduces the Member actor, Harbor's web, mobile, operations, application, and database systems, plus vendor digital banking, legacy core, and fintech systems. Explicit ownership and category enums make boundaries visible. A validated directed graph and CLI turn the architecture into executable behavior.

See the chapter's [Mermaid architecture diagram](diagrams/digital-banking-integration-ecosystem.md).

[Chapter 1: Building a Deterministic Integration Laboratory](chapters/01-deterministic-integration-laboratory.md) adds controlled clocks, identifiers, scenario fixtures, and simulated vendor states. This foundation comes before REST, SOAP, databases, and frontend behavior so later integration lessons remain repeatable without live infrastructure. See its [Mermaid architecture diagram](diagrams/deterministic-laboratory.md).

[Chapter 2: Modeling the Member Domain](chapters/02-member-domain-modeling.md) defines Harbor's internal Member, Account, typed identifiers and statuses, and integer-minor-unit Money. Vendor representations remain outside this boundary. See the [member domain diagram](diagrams/member-domain-model.md).

[Chapter 3: Vendor Platform Boundaries](chapters/03-vendor-platform-boundaries.md) introduces the Harbor-owned gateway, explicit vendor identities, deterministic Northstar model, and adapter that translates vendor language into Harbor domain meaning. See the [vendor boundary diagram](diagrams/vendor-platform-boundary.md).

The progression is deliberate: Chapter 0 maps the **system landscape**, Chapter 1 supplies **controlled deterministic infrastructure**, Chapter 2 defines the **Harbor internal member domain**, and Chapter 3 establishes the **vendor/domain boundary**.

## Requirements and installation

- PHP 8.2 or newer
- Composer is optional for Chapters 0 and 1

Clone the repository and enter it. The implemented chapters have no third-party packages, so they run immediately:

```bash
git clone <repository-url> executable-digital-banking-integration-laboratory
cd executable-digital-banking-integration-laboratory
```

If you want Composer to generate its standard autoloader and validate the package metadata:

```bash
composer install
```

The checked-in `bootstrap.php` means core commands remain offline and dependency-free.

## Run the laboratory

Inspect the Chapter 0 ecosystem:

Print the complete ecosystem in stable ownership groups:

```bash
./bin/digital-banking-lab ecosystem
```

Trace the Member Web feature path across Harbor and vendor boundaries:

```bash
./bin/digital-banking-lab path
```

Execute each Chapter 1 scenario:

```bash
./bin/digital-banking-lab laboratory normal-operation
./bin/digital-banking-lab laboratory vendor-timeout
./bin/digital-banking-lab laboratory vendor-unavailable
```

Verify that two independently constructed runs have the same observable result:

```bash
./bin/digital-banking-lab determinism normal-operation
```

Render the deterministic Chapter 2 member summary:

```bash
./bin/digital-banking-lab member member-0001
```

Translate the same member through Northstar, inspect the explicitly separate identities, and compare domain meaning:

```bash
./bin/digital-banking-lab vendor-member member-0001
./bin/digital-banking-lab vendor-map member-0001
./bin/digital-banking-lab compare-member member-0001
```

Run all tests:

```bash
php tests/run.php
# or, when Composer is installed:
composer test
```

## Repository map

```text
bin/          Observable laboratory commands
chapters/     Textbook chapters and exercises
diagrams/     Architecture diagrams
src/Domain/   Business and architecture concepts
src/Fixtures/ Deterministic fictional scenarios
src/Application/ Use cases and output rendering
tests/        Dependency-free executable architecture tests
```

Future chapters will extend these same boundaries rather than replacing them. Chapter 4's natural objective is **defining Harbor's REST API contract independently of the vendor transport behind it**; it is not implemented here.

## Roadmap

Chapters 0 through 3 are implemented. The remaining entries describe direction, not existing chapters.

1. **Digital banking ecosystem** — systems, actors, ownership, and integration paths (Chapter 0 — implemented)
2. **Deterministic laboratory setup** — repeatable fixtures and simulation conventions (Chapter 1 — implemented)
3. **Member domain modeling** — safe fictional member and account value objects (Chapter 2 — implemented)
4. **Vendor platform boundaries** — contracts and division of responsibility (Chapter 3 — implemented)
5. **REST API fundamentals** — resources, methods, status codes, and schemas
6. **Consuming vendor REST APIs** — deterministic vendor API clients
7. **SOAP and legacy banking integrations** — envelopes, contracts, and translation
8. **Integration adapters** — isolate protocols from application behavior
9. **Vendor failure handling** — timeouts, retries, idempotency, and safe degradation
10. **PHP application services** — orchestrate domain work and integrations
11. **SQL for digital banking** — MySQL-oriented schemas and queries
12. **Data-driven development** — measurements, hypotheses, and responsible analytics
13. **Member web experience** — semantic HTML, CSS, accessibility, and mobile-first design
14. **Frontend state and API consumption** — JavaScript and TypeScript clients
15. **Digital self-service workflows** — usable navigation and safe conversions
16. **Third-party fintech integration** — capability and risk evaluation
17. **Secure input validation** — trust boundaries and defensive handling
18. **Protecting financial data** — minimization, masking, and safe logging
19. **Unit testing** — fast tests around domain and application behavior
20. **Integration testing** — deterministic tests at system boundaries
21. **Full-stack debugging** — trace failures across browser, services, and adapters
22. **Git and code-review workflows** — reviewable changes and delivery discipline
23. **Digital experience optimization** — analytics, usability, conversion, navigation, and SEO
24. **End-to-end digital banking integration laboratory** — assemble and assess the complete flow

## Safety and scope

Never put real member information or credentials in this repository. All institutions, people, APIs, responses, and transactions in the laboratory must be fictional or deterministic simulations. Examples are educational and are not production security, compliance, legal, or architecture guidance.
