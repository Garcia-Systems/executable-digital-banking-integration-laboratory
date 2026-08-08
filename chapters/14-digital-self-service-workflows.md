# Chapter 14: Digital Self-Service Workflows and Form Validation

## Educational question

**How should Harbor accept a member-initiated digital action while validating input consistently across the browser, API, application layer, and domain boundary?**

## Learning objectives

You will distinguish UX validation from authoritative validation, model a typed TypeScript form and immutable Harbor command, POST machine-readable money, return stable field errors, and manage editing, submitting, succeeded, and failed states.

## Banking concept

Digital self-service lets a member initiate work without employee intervention: transfers, profile changes, card controls, verification, or account servicing. This chapter intentionally implements only **transfer preview**, because validating an instruction is safer to study than mutating financial state.

> **This chapter does not implement a financial transfer. It implements a validated transfer-intent preview. No balance or ledger is changed.**

The fictional laboratory requires two distinct OPEN accounts belonging to the requested member, positive integer USD minor units no greater than current available balance, and an optional memo of at most 140 characters. Equal-to-balance is accepted and projects zero. These teaching rules are not a complete real credit-union policy.

## Engineering concept

Validation is layered:

```text
Browser validation
↓
HTTP request validation
↓
Harbor value construction
↓
Application/domain validation
↓
External-data checks
```

The same rule can appear twice for different responsibilities. `amount > 0` gives immediate browser feedback, while the backend authoritatively enforces it. Ownership, OPEN status, and available balance require current Harbor state and cannot be authoritative in the browser.

### Never trust the browser as the authority

Browser code can be bypassed, other API clients exist, stale frontend versions remain in use, and requests can be manually constructed. Harbor therefore validates every request server-side. Invalid JSON or a non-object body is `400`; a valid JSON object with invalid fields is `422` with stable API field keys.

### Money input

`parseFloat("12.34") * 100` is not the preferred financial conversion because binary floating point is unnecessary. `parseUsdAmountToMinorUnits` parses the string: `"12.34"` becomes major `"12"` plus minor `"34"`, then integer `1234`. It rejects symbols, commas, negatives, blanks, malformed text, and more than two decimal places.

### Preview versus execution

A preview validates, calculates, and returns the expected interpretation without mutation. Execution changes state and needs stronger authorization, idempotency, auditability, and transaction semantics. Disabling the button while submitting prevents duplicate clicks as UX protection; it is not server idempotency. There is no automatic retry of this mutation-like request.

### Validation errors versus system failures

“Source and destination must be different” is actionable field feedback. “A required upstream service is unavailable” is a system failure, not an invalid field. Harbor retains existing safe failure mapping and never exposes vendor diagnostics.

## Architecture

See the [digital self-service validation diagram](../diagrams/digital-self-service-validation.md). JSON becomes Harbor-owned `MemberId`, `AccountId`, `Money`, and `PreviewTransferCommand` in the controller. `PreviewTransfer` knows neither HTTP nor vendor types and uses only Harbor ports. `preview-0001` is deterministic preview identity, **not a transaction ID**.

## Implementation

`POST /api/members/{memberId}/transfer-preview` accepts account IDs, `{ "currency": "USD", "minorUnits": 50000 }`, and nullable memo. `PreviewTransfer` loads the member, verifies accounts and statuses, reads source availability, accepts an amount equal to availability, subtracts integer minor units, and returns an immutable `TransferPreview`. The presenter adds deterministic formatting.

Member Web keeps transfer state separate from Chapter 13 member-loading state. Local errors prevent HTTP. A valid request becomes submitting and disables its real button. Success renders both balances and “No funds have been moved.” A `422` maps field errors; dependency and contract failures use safe general wording. Any source, destination, amount, or memo edit invalidates the old preview.

## Run the laboratory

```bash
php -S 127.0.0.1:8080 -t public
cd apps/member-web && npm install && npm run dev
```

Choose `account-0001`, `account-0002`, and `500.00`. The deterministic result uses `$2,385.75` available and `$1,885.75` projected.

## What to observe

* Friendly decimal input becomes integer minor units before POST.
* Server validation remains authoritative.
* Field errors are associated with controls; system errors use an alert.
* Editing successful input removes stale preview output.
* No persistence, payment rail, fintech call, ledger, or balance mutation occurs.

## Engineering tradeoffs

The controller validates transport shape but delegates orchestration. Money permits negative values consistently with earlier balance semantics; the service rejects insufficient funds before subtraction. Preview IDs are deterministic and ephemeral. Actual execution must address authorization, idempotency, audit, and atomic state change rather than copying preview semantics.

## Automated tests

Backend tests cover subtraction, preview rules, endpoint status/contract, deterministic projection, and safe failure mapping. Frontend tests cover string parsing, local validation, submission states, server errors, accessibility rendering, and stale-preview invalidation. Existing Chapter 12 and 13 suites remain regression protection.

## Exercise

Add **Transfer purpose** with Harbor values `SAVINGS`, `BILL_PAY`, and `OTHER`. Add a Harbor-owned enum/value, frontend selection, API validation, intentional mapping, preview output, and frontend/backend tests. Reject unknown values and avoid vendor terminology. Which layers must know this field? Which layers should not? Do not implement execution or a vendor mapping merely to complete the exercise.
