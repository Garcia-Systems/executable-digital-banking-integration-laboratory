# Chapter 6: SOAP and Legacy Banking Integrations

## Educational question

How can Harbor integrate with a legacy SOAP-based banking system without allowing XML, SOAP envelopes, or legacy field names to leak into the rest of the application?

## Learning objectives

After this chapter, you can describe a SOAP envelope and distinguish it from a domain message; build and parse deterministic SOAP XML; isolate legacy identities and terminology; classify SOAP Faults; test without a live service; and trace REST and SOAP integrations behind Harbor-owned contracts.

## Banking concept

This laboratory's entirely fictional **Heritage Core Banking System** is a system of record for selected internal account attributes. Northstar remains the digital-banking presentation platform. This arrangement is specific to this teaching laboratory, not a universal banking architecture.

Financial institutions may combine modern digital platforms, REST APIs, legacy cores, SOAP/XML services, batch interfaces, and vendor-owned systems. “Legacy” does not mean inherently bad: a system may be mature, stable, deeply integrated, and difficult or expensive to replace. The challenge is often safe interoperability rather than wholesale replacement.

Heritage distinguishes **ledger balance** from **available balance**. External availability of those fields does not require changing Harbor's simplified `Account`. The focused `AccountBalanceDetails` capability carries an `AccountId`, two `Money` values, and the existing `AccountStatus`.

## Engineering concept

The separation is deliberate:

```text
SOAP transport → Heritage model → Harbor adapter → Harbor application model
```

For example:

```text
<LedgerBalanceMinorUnits>245075</LedgerBalanceMinorUnits>
→ ledgerBalanceMinorUnits = 245075
→ Money::usd(245075)
→ ledgerBalance = $2,450.75
```

The application does not know where the value came from. Likewise, `account-0001`, Northstar's `NS-PROD-9001`, and Heritage's `HC-100045` belong to three distinct identity namespaces.

### REST versus SOAP

| Concern | Northstar REST | Heritage SOAP |
|---|---|---|
| Request envelope | HTTP request | XML SOAP Envelope and Body |
| Representation | JSON | XML |
| Operation style | Resource-oriented endpoint | Named `GetAccountDetails` operation |
| Error signaling | HTTP status plus JSON | Transport status plus SOAP Body/Fault |
| Parsing | Strict JSON shape | Namespace-aware XML structure |
| Coupling risk | Vendor fields and routes | Legacy elements, namespaces, and operations |

Neither style is always better. They have different contracts and operational characteristics. Crucially, HTTP 200 is only transport success: its SOAP body can contain a `soap:Fault`, meaning the operation failed.

## Architecture

See the [SOAP legacy integration diagram](../diagrams/soap-legacy-integration.md). `GetAccountBalanceDetails` depends on a Harbor gateway. The adapter understands Harbor identity and Heritage meaning; the client builds the request, invokes transport, parses namespaced XML, validates fields, and returns only Heritage models. XML stops there.

## Implementation

`HeritageSoapEnvelopeBuilder` produces the stable fictional `GetAccountDetails` envelope. `SoapTransport` accepts endpoint, action, and XML and returns status plus XML. `DeterministicHeritageSoapTransport` records requests and returns memory-only fixtures from the `.invalid` endpoint—no sockets, sleeps, credentials, or real service.

`HeritageSoapClient` rejects non-success transport statuses, malformed XML, missing fields, unexpected operations/namespaces, and SOAP Faults. `ACCOUNT_NOT_FOUND` and `CORE_ERROR` have explicit fault types even when HTTP is 200. The adapter supports `OPEN`, `RESTRICTED`, and `CLOSED`, rejects unknown statuses and non-USD currency, and performs no floating-point arithmetic.

## Run the laboratory

```bash
./bin/digital-banking-lab core-account account-0001
./bin/digital-banking-lab soap-request account-0001
./bin/digital-banking-lab integration-styles
./bin/digital-banking-lab core-account account-0001 --scenario=account-not-found
./bin/digital-banking-lab core-account account-0001 --scenario=soap-fault
./bin/digital-banking-lab core-account account-0001 --scenario=malformed-xml
./bin/digital-banking-lab core-account account-0001 --scenario=incomplete-response
./bin/digital-banking-lab core-account account-0001 --scenario=unsupported-status
```

## What to observe

The normal projection contains Harbor's account identifier, balances, and status—but not `HC-100045`. The inspection command intentionally exposes exact diagnostic XML. Fault output names both HTTP 200 and SOAP failure, demonstrating that syntactic and transport success do not imply operation success.

## Engineering tradeoffs

Small explicit XML code makes the lesson visible and avoids a generic enterprise bus. A production client would also address observability, schema evolution, secure parser policy, service authentication, and operational resilience. Replacing Heritage's interface would require a new gateway adapter, not changes to the application service.

## Automated tests

The dependency-free suite checks deterministic and escaped envelopes, typed parsing, integer money, namespaces and required fields, fault classification, translation failures, explicit identities, no-network transport, application independence, architecture boundaries, and all prior REST/API regressions.

```bash
php tests/run.php
```

## Exercise

Add a fictional SOAP operation named `GetAccountRestrictions` whose response contains `debitAllowed` and `withdrawalAllowed`. Do not begin by editing `Account`. Decide:

1. whether those values belong on `Account` or a focused Harbor representation;
2. where SOAP parsing belongs;
3. where Heritage terminology stops;
4. how each SOAP Fault is tested;
5. how XML stays out of application logic; and
6. which deterministic scenarios prove the boundary.

Chapter 7's natural objective is to identify integration adapters as a reusable architectural pattern across multiple external systems; that work is intentionally not implemented here.
