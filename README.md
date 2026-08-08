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

[Chapter 4: Designing Harbor's REST API](chapters/04-rest-api-fundamentals.md) exposes a read-only Harbor-owned Member Summary through a thin HTTP boundary, transport-independent application service, explicit presenter, and stable error contract. See the [REST API diagram](diagrams/harbor-rest-api.md).

[Chapter 5: Consuming a Vendor REST API](chapters/05-consuming-vendor-rest-apis.md) adds a deterministic HTTP transport, strict Northstar REST client, typed failure taxonomy, and REST-backed application composition while preserving Harbor's public contract. See the [vendor REST integration diagram](diagrams/vendor-rest-integration.md).

[Chapter 6: SOAP and Legacy Banking Integrations](chapters/06-soap-and-legacy-banking-integrations.md) adds the fictional Heritage core, deterministic SOAP/XML transport, strict fault parsing, explicit legacy identities, and a focused Harbor balance-details capability. See the [SOAP legacy integration diagram](diagrams/soap-legacy-integration.md).

[Chapter 7: Integration Adapters as a Reusable Pattern](chapters/07-integration-adapters.md) makes Harbor-owned ports, vendor adapters, vendor clients, transport placement, dependency inversion, composition, and architecture checks explicit without forcing unrelated capabilities into one interface. See the [integration adapter pattern diagram](diagrams/integration-adapter-pattern.md).

[Chapter 8: Handling Vendor Failures Safely](chapters/08-vendor-failure-handling.md) unifies REST and SOAP failures behind Harbor-owned categories, explicit retry dispositions, safe public mappings, and deterministic operator diagnostics—without implementing retries. See the [vendor failure handling diagram](diagrams/vendor-failure-handling.md).

[Chapter 9: PHP Application Services and Orchestration](chapters/09-php-application-services.md) composes member lookup and core balance capabilities through an explicit, reusable application service, immutable result, thin HTTP controller, presenter, and deterministic teaching trace. See the [PHP application orchestration diagram](diagrams/php-application-orchestration.md).

[Chapter 10: SQL for Digital Banking](chapters/10-sql-for-digital-banking.md) adds a deterministic Harbor-owned SQLite operational projection, explicit schema and fixtures, parameterized SQL, row mapping, inactivity reasoning, and query-plan inspection. It is not a ledger and does not replace vendor integrations. See the [SQL data-access diagram](diagrams/sql-data-access.md). The SQL remains MySQL-oriented where practical, with differences documented in the chapter.

[Chapter 11: Data-Driven Development](chapters/11-data-driven-development.md) turns those relational facts into explicit derived metrics and explainable Harbor decisions through an activity policy, profile, and operational review. See the [data-driven development diagram](diagrams/data-driven-development.md).

[Chapter 12: Building the Member Web Experience](chapters/12-member-web-experience.md) adds a mobile-first HTML/CSS/TypeScript frontend that validates and renders Harbor's public member API through explicit loading, loaded, empty, and safe error states. Vendor complexity stays behind the backend boundary. See the [Member Web experience diagram](diagrams/member-web-experience.md).

The progression is deliberate: Chapter 0 maps the **system landscape**; Chapter 1 supplies a **deterministic laboratory**; Chapter 2 defines the **Harbor domain**; Chapter 3 establishes the **vendor boundary**; Chapter 4 defines the **Harbor REST API**; Chapter 5 adds **vendor REST integration**; Chapter 6 adds **legacy SOAP integration**; Chapter 7 extracts **ports and adapters**; Chapter 8 adds **unified failure handling**; Chapter 9 adds **PHP application orchestration**; Chapter 10 adds **SQL**; Chapter 11 adds **data-driven development**; and Chapter 12 adds the **Member Web frontend**.

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

Inspect Chapter 5's deterministic vendor REST request and representative scenarios:

```bash
./bin/digital-banking-lab vendor-rest member-0001
./bin/digital-banking-lab vendor-rest member-0001 --scenario=vendor-timeout
./bin/digital-banking-lab vendor-rest member-0001 --scenario=vendor-unavailable
./bin/digital-banking-lab vendor-rest member-0001 --scenario=customer-not-found
./bin/digital-banking-lab vendor-rest member-0001 --scenario=vendor-error
./bin/digital-banking-lab vendor-rest member-0001 --scenario=malformed-json
./bin/digital-banking-lab vendor-rest member-0001 --scenario=incomplete-response
./bin/digital-banking-lab vendor-rest member-0001 --scenario=unsupported-product
```

Inspect Chapter 6's Harbor balance capability, exact SOAP envelope, and integration-style comparison:

```bash
./bin/digital-banking-lab core-account account-0001
./bin/digital-banking-lab soap-request account-0001
./bin/digital-banking-lab integration-styles
```

Inspect Chapter 7's deterministic integration catalog, individual architectural paths, and executable boundary rules:

```bash
./bin/digital-banking-lab integrations
./bin/digital-banking-lab integration northstar-digital-banking
./bin/digital-banking-lab integration heritage-core-banking
./bin/digital-banking-lab architecture-check
```

Inspect one Chapter 8 operator diagnostic or the complete deterministic failure matrix:

```bash
./bin/digital-banking-lab failure northstar-timeout
./bin/digital-banking-lab failure heritage-malformed-xml
./bin/digital-banking-lab failure-matrix
```

Compose both integration capabilities through Chapter 9's application service and inspect its Harbor-only trace:

```bash
./bin/digital-banking-lab member-overview member-0001
./bin/digital-banking-lab member-overview-trace member-0001
```

Explore Chapter 10's deterministic operational database and SQL reasoning:

```bash
./bin/digital-banking-lab db-members
./bin/digital-banking-lab account-activity account-0001
./bin/digital-banking-lab inactive-members --days=180
./bin/digital-banking-lab sql-example inactive-members
./bin/digital-banking-lab sql-pitfall inactivity
./bin/digital-banking-lab explain inactive-members
```

Explore Chapter 11's explicit facts, policy, and decisions:

```bash
./bin/digital-banking-lab member-activity member-0001
./bin/digital-banking-lab activity-review
./bin/digital-banking-lab activity-review --days=90
./bin/digital-banking-lab explain-activity member-0002
```

Start the Chapter 4 local API, then request success and representative errors from another terminal:

```bash
php -S 127.0.0.1:8080 -t public
curl -i http://127.0.0.1:8080/api/members/member-0001
curl -i http://127.0.0.1:8080/api/members/member-0001/financial-overview
curl -i http://127.0.0.1:8080/api/members/member-9999
curl -i http://127.0.0.1:8080/api/members/
curl -i -X POST http://127.0.0.1:8080/api/members/member-0001
curl -i http://127.0.0.1:8080/api/unknown
```

Run all tests:

```bash
php tests/run.php
# or, when Composer is installed:
composer test
```

## Member Web

Run the backend API and frontend in separate terminals:

```bash
php -S 127.0.0.1:8080 -t public
```

```bash
cd apps/member-web
npm install
npm test
npm run dev
```

Open `http://127.0.0.1:5173`; the API is at `http://127.0.0.1:8080`. The expected page welcomes fictional member Avery Morgan and shows Everyday Checking and Primary Savings. Use `npm run typecheck` and `npm run build` for frontend verification. PHP and Node test suites remain intentionally separate.

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

Future chapters will extend these same boundaries rather than replacing them. Chapter 13 will focus on **frontend request state, resilience, and API consumption patterns**.

## Roadmap

Chapters 0 through 12 are implemented. The remaining entries describe direction, not existing chapters.

1. **Digital banking ecosystem** — systems, actors, ownership, and integration paths (Chapter 0 — implemented)
2. **Deterministic laboratory setup** — repeatable fixtures and simulation conventions (Chapter 1 — implemented)
3. **Member domain modeling** — safe fictional member and account value objects (Chapter 2 — implemented)
4. **Vendor platform boundaries** — contracts and division of responsibility (Chapter 3 — implemented)
5. **REST API fundamentals** — resources, methods, status codes, and schemas (Chapter 4 — implemented)
6. **Consuming vendor REST APIs** — deterministic vendor API clients (Chapter 5 — implemented)
7. **SOAP and legacy banking integrations** — envelopes, contracts, and translation (Chapter 6 — implemented)
8. **Integration adapters** — reusable Harbor ports, vendor adapters, clients, and composition (Chapter 7 — implemented)
9. **Vendor failure handling** — classification, containment, retry disposition, and safe translation (Chapter 8 — implemented)
10. **PHP application services** — orchestrate domain work and integrations (Chapter 9 — implemented)
11. **SQL for digital banking** — MySQL-oriented schemas and queries (Chapter 10 — implemented)
12. **Data-driven development** — relational facts interpreted by explicit application policy (Chapter 11 — implemented)
13. **Member Web** — Harbor API consumption with HTML, CSS, and TypeScript (Chapter 12 — implemented)
14. **Frontend request state, resilience, and API consumption patterns** — Chapter 13
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
