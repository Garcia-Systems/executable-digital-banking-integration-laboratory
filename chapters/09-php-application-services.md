# Chapter 9: PHP Application Services and Orchestration

## Educational question

How should Harbor coordinate multiple domain and integration capabilities into a complete application use case without putting orchestration logic in controllers, adapters, or domain objects?

## Learning objectives

By the end of this chapter, you can distinguish application orchestration from domain and integration logic; define a use-case boundary; constructor-inject Harbor-owned ports; sequence multiple capabilities; build an immutable application result; propagate Harbor failures; and test the use case without HTTP or live vendors.

## Banking concept

A member experiences one simple idea: **my accounts and balances**. Producing that view can require Northstar's digital-banking account projection and Heritage's focused core balance details. Harbor coordinates those capabilities, and the member need not know which external system supplied a field.

This laboratory deliberately exposes three concepts:

* **Digital banking displayed balance** is the balance in the digital projection.
* **Ledger balance** is the core detail labeled as ledger balance.
* **Available balance** is the core detail labeled as available balance.

These are distinct values; the laboratory does not claim a universal calculation rule for banks. For `account-0001`, the fixture's displayed and ledger balances both happen to be $2,450.75, while available balance is $2,385.75. For `account-0002`, all three happen to be $8,120.00. Equality is a fixture choice, not an invariant. Every value remains integer minor units—never floating point.

## Engineering concept

Four responsibilities remain separate:

* **Domain logic** expresses rules intrinsic to Harbor concepts.
* **Integration logic** translates between Harbor and an external system.
* **Application orchestration** sequences capabilities to complete a Harbor use case.
* **Presentation logic** converts an application result into intentional JSON or CLI text.

`GetMemberFinancialOverview` is an application service. It retrieves a `Member`, requests details for each account in member order, verifies returned account identity, and constructs a complete result. It imports no vendor or transport class.

Controller orchestration would produce a poor path: controller → Northstar → parse → loop → Heritage → combine arrays → JSON. Harbor instead uses controller → `GetMemberFinancialOverview` → presenter. The use case is independently testable, reusable by CLI, HTTP-independent, and vendor-independent.

Nor should `Member` call gateways. An entity represents domain state and invariants; it should not locate services or retrieve external state. Service-locator or active-record-style coupling would hide dependencies and make deterministic testing harder.

## Architecture

See the [PHP application orchestration diagram](../diagrams/php-application-orchestration.md). The composition root chooses Northstar and Heritage adapters. The application service receives only `DigitalBankingGateway` and `AccountBalanceGateway` through constructor injection.

## Implementation

The immutable `MemberFinancialOverview` composes the existing `Member` with a list of `AccountFinancialOverview` values. Each account result composes the existing `Account` with ledger and available `Money`; it does not duplicate account fields into primitive arrays.

The sequence is deliberately synchronous and observable:

1. Retrieve the Harbor member.
2. Iterate accounts in their domain order.
3. Retrieve each account's balance details.
4. Reject a returned `AccountId` that differs from the requested ID.
5. Construct and return the complete overview.

A missing account or temporary dependency failure remains a Harbor-owned `IntegrationFailure`. Chapter 9's product policy is to fail the entire overview rather than silently return partial data. Exceptions are not discarded or converted back into Heritage types. Because processing is sequential, failure on the second lookup stops work at that point.

`ApplicationTrace` is a small deterministic teaching recorder, not production telemetry. The HTTP controller parses `MemberId`, invokes one use case, delegates representation to `MemberFinancialOverviewPresenter`, and reuses Chapter 8's centralized public failure mapper.

## Run the laboratory

```bash
./bin/digital-banking-lab member-overview member-0001
./bin/digital-banking-lab member-overview-trace member-0001
php -S 127.0.0.1:8080 -t public
curl http://127.0.0.1:8080/api/members/member-0001/financial-overview
```

## What to observe

The normal command shows two accounts and all three balance concepts. The trace names Harbor ports only: it contains neither raw Northstar JSON nor Heritage XML or vendor identifiers. The original `/api/members/member-0001` resource remains unchanged; the financial overview is a new projection.

## Engineering tradeoffs

Sequential calls favor simplicity, deterministic order, and teaching visibility. Production systems might evaluate batching or concurrency based on latency, rate limits, and vendor constraints, but this chapter adds no promises, fibers, queues, retries, or asynchronous infrastructure.

Failing the complete result prevents ambiguous partial output, but it is a product policy rather than a universal answer. Another use case could intentionally model partial results, provided its contract makes that choice explicit.

## Automated tests

The dependency-free suite tests port-only dependencies, member-first sequencing, account order, integer money, both fixture accounts, identity mismatch, complete-result failure policy, propagation and stopping behavior, trace determinism, explicit presentation, safe HTTP mapping, and the unchanged Chapters 0–8 behavior.

```bash
php tests/run.php
./bin/digital-banking-lab architecture-check
```

## Exercise

Design (do not implement here) `GetMemberLiquiditySummary`, using only Harbor-owned `Money`, to calculate total ledger and available balances. Decide:

1. Should this live in a controller?
2. Should it live in Northstar?
3. Should it live in Heritage?
4. Should it live in a new application service or domain service?
5. What happens if one account lookup fails?
6. How are `Money` values summed safely without floating point?
7. Which tests prove deterministic behavior?

Do not introduce database behavior. Chapter 10 will introduce SQL and MySQL concepts for digital-banking data access.
