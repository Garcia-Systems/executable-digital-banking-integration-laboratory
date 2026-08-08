# Chapter 17: Protecting Sensitive Member and Financial Data

## Educational question

**How should Harbor minimize, control, and safely expose sensitive member and financial information across APIs, application services, integrations, diagnostics, and the browser?** Chapter 16 asked whether Harbor can validate data crossing a boundary. Chapter 17 asks whether a layer should receive, retain, expose, or log that data at all.

The core principle is: **A component should receive only the data required to perform its responsibility.**

## Learning objectives

After this chapter, you can identify sensitive information; apply data minimization and least knowledge; distinguish `PUBLIC`, `INTERNAL`, `MEMBER_SENSITIVE`, and `SECRET`; review API fields; prevent sensitive diagnostic output; isolate credentials; avoid unnecessary browser persistence; distinguish masking from security; and trace representation changes across boundaries. This small teaching taxonomy does not map directly to a regulatory framework.

## Banking concept

Financial applications naturally handle information members consider private: identity, account relationships, balances, activity, verification state, and member-entered instructions. Safe handling is more than rejecting invalid input. Harbor must control where information flows, which component receives it, where it is retained and displayed, and what reaches diagnostics.

Member Web needs a display name, Harbor account names and identifiers, relevant balances, and Harbor workflow statuses. It does not need a Northstar customer key, Heritage account number, ClearVerify subject/reference, SQL text, or vendor credential. A failure record needs a system, operation, Harbor category, retry disposition, and stable code—not a member record, balances, raw JSON/XML, or an Authorization header.

## Engineering concept

**Validate what enters. Minimize what flows. Control what leaves.**

A transfer-preview request contains source account, destination account, amount, and memo. ClearVerify receives only `MemberId`; Heritage receives only the source `AccountId`; Northstar performs only member lookup. Sending the complete command to every dependency would increase exposure and coupling without adding capability.

### Least knowledge

A component should know only what it needs. Smaller contracts reduce accidental exposure, clarify tests, reduce coupling, and make vendor replacement easier. Ports express Harbor capabilities in Harbor types: `MemberVerificationGateway` accepts `MemberId`, `AccountBalanceGateway` accepts `AccountId`, and presenters accept Harbor-owned results rather than clients or provider DTOs.

### Data minimization versus masking

Minimization omits an unnecessary value. Masking shows only part of a value. When ClearVerify does not need a memo, omission is stronger than sending `********`. **Masking is not access control**: the original still exists and masking merely reduces accidental display exposure. Harbor does not invent or retain full account numbers for this lesson.

### Identifier exposure

`member-0001` and `account-0001` are Harbor API identities that clients need. They remain intentionally exposed and are member-sensitive. `NS-CUST-4417` and vendor product/account identities serve internal mapping and remain absent. An identifier is exposed according to application need, not masked merely because it is an identifier.

## Architecture

The [sensitive-data-flow diagram](../diagrams/sensitive-data-flow.md) shows minimal outbound capabilities, representation changes on the return path, a separate secret boundary, and whitelisted diagnostics.

| Data | Domain | Integration | API | Browser | Diagnostics |
|---|---|---|---|---|---|
| Member name | Yes | translated from Northstar | scoped response | runtime | No |
| Harbor MemberId | Yes | mapping input | Yes | runtime | Prefer no |
| Northstar customer key | No | identity mapping | No | No | No by default |
| Account balance | Yes | Northstar/Heritage boundary | where required | runtime where required | No |
| Transfer memo | Application preview | no external vendor in Chapter 17 | preview only | runtime | No |
| Vendor token | No | client transport only | No | No | No |

The `SensitiveDataFlow` catalog records four educational paths—member summary, balance details, verification, and transfer preview—and the representation at every step. Runtime behavior does not depend on this metadata.

## Implementation

### Classification and inventory

`DataElementDescriptor` records classification, primary use, API/diagnostic disposition, and Member Web disposition in deterministic order. Public institution/help/documentation data is `PUBLIC`; diagnostic/vendor/operation/architecture metadata is `INTERNAL`; member name and Harbor IDs, balances, amount, memo, verification, and timestamps are `MEMBER_SENSITIVE`; transport credentials and future signing-key placeholders are `SECRET`.

### Intentional API projections

Presenters explicitly select Harbor fields. Member summary retains Harbor IDs, names, account display details, and balances because the existing experience requires them. Verification exposes only Harbor member identity and status. Transfer preview exposes the amount, memo, and balance projection needed for confirmation, but not vendor IDs, references, HTTP/SOAP details, or diagnostic codes. Public errors contain stable client codes and safe language only.

### Operational logging

Poor: “Transfer preview failed for Avery Morgan transferring $500 … raw response …”. Better: operation `transfer_preview`, external system `Heritage`, category `TEMPORARY_UNAVAILABLE`, code `HERITAGE_UNAVAILABLE`. `OperationalEventRecorder` accepts only an `IntegrationFailureEvent`; there is no `logger->error($message, $everything)` context bag. The deterministic recorder is in memory for tests only. Controlled investigation tooling might sometimes need more detail, but it is not implemented here.

### Secret isolation

Credentials belong in client/transport infrastructure configuration, never in `Member`, `TransferPreview`, application constructors, frontend JavaScript, or DTOs. This laboratory currently has no vendor credential value to centralize and intentionally does not invent one. Real secret storage, rotation, and encryption are out of scope.

### Browser storage and URLs

Member Web keeps summaries, verification, and previews only in runtime object/DOM state and fetches again after reload. It does not write `localStorage`, `sessionStorage`, IndexedDB, or frontend cookies and does not console-log DTOs. In-memory data is not inherently secure; the decision merely avoids unnecessary durable persistence. Offline persistence could improve UX but introduces invalidation, exposure, and stale-data concerns.

Transfer preview remains a `POST` JSON body. Amount and memo do not enter its URL, where values may otherwise reach history, logs, analytics, copied links, or referrers. A POST body is **not automatically secret**; transport protection and future authentication are separate concerns.

### Retention in this laboratory

| Data | Retention in current lab |
|---|---|
| Member API DTO | runtime only |
| Transfer preview | runtime only |
| Operational diagnostic | in-memory test recorder only |
| Vendor fixtures | static fictional fixtures |
| Real member data | not stored |

Production systems require explicit retention policies; this chapter invents no legal retention periods.

## Run the laboratory

```bash
./bin/digital-banking-lab data-inventory
./bin/digital-banking-lab data-flow transfer-preview
./bin/digital-banking-lab api-data-check
./bin/digital-banking-lab security-check
./bin/digital-banking-lab architecture-check
php tests/run.php
cd apps/member-web && npm test && npm run typecheck
```

`api-data-check` is a deterministic educational inspection, not a security certification.

## What to observe

Trace what changes at every boundary. Northstar JSON becomes a Harbor `Member`; Heritage SOAP becomes `AccountBalanceDetails`; ClearVerify status becomes `VerificationStatus`; presenters emit deliberately scoped DTOs. In transfer preview, neither memo nor amount reaches verification, and neither memo nor destination account reaches Heritage.

## Engineering tradeoffs

Stable public contracts are preserved where the member experience needs them. Omitting data is preferred to masking. Generic diagnostics omit even Harbor `MemberId`; this makes correlation less convenient but reduces default exposure. Authentication, authorization, transport guarantees, production secret management, encryption, auditing, controlled investigative access, and formal retention enforcement remain explicit future security scope. This chapter claims no compliance outcome and is not a DLP system.

## Automated tests

Backend tests cover classification, flow metadata, public DTO omissions, minimal gateway arguments, minimized result objects, safe error/event fields, and Chapters 0–16 regression. Frontend tests statically forbid durable storage/cookies/console logging and inspect the actual transfer request to prove amount and memo remain in a POST body rather than its URL. Existing safe-rendering, verification, transfer, and request-state suites continue to run.

## Exercise

Design—but do not implement—a **Member contact preference** with `EMAIL`, `SMS`, and `NONE`.

1. Is it member-sensitive?
2. Which application service needs it?
3. Should every API expose it?
4. Does ClearVerify need it?
5. Does it belong in diagnostics?
6. Should Member Web persist it?
7. Which minimization tests prove those choices?

The key insight: adding a field to a domain object does not mean every boundary should automatically expose it.

The natural objective of Chapter 18 is automated unit testing as an engineering discipline across Harbor’s domain, application, integration, and frontend layers. Chapter 18 is not implemented here.
