# Chapter 8: Handling Vendor Failures Safely

## Educational question

**How should Harbor classify, contain, and translate failures from external banking systems without leaking vendor details or destabilizing the member experience?**

## Learning objectives

After this chapter, you can distinguish transport, protocol, decoding, semantic, and application failures; identify potentially retryable failures; translate vendor exceptions at an adapter; preserve stable API errors; separate member-safe output from operator evidence; and test failure paths deterministically.

## Banking concept

A member-facing experience depends on systems outside Harbor's control. A network can fail, a vendor can be slow or unavailable, the vendor can reject an operation, a payload can be malformed, or a newly introduced vendor value can be unknown to Harbor. These are materially different events. Members generally need useful, stable guidance—not a vendor's URL, SOAP vocabulary, payload, or exception class. Engineers still need bounded evidence that identifies the system, operation, classification, and diagnostic code.

## Engineering concept

Failure translation is an architectural boundary just like data translation. Northstar's `DDA` does not belong in the Harbor domain; likewise, `NorthstarTimeoutFailure` does not belong in `GetMemberSummary`. The layered flow is:

```text
External failure -> vendor/client failure -> adapter translation
-> Harbor IntegrationFailure -> application context -> public API error
                                      \-> operational diagnostic
```

A Northstar timeout becomes internal diagnostic `NORTHSTAR_TIMEOUT`, Harbor meaning `TIMEOUT / RETRYABLE`, and public behavior `504 / upstream_timeout`. Heritage malformed XML becomes `HERITAGE_INVALID_XML`, `INVALID_EXTERNAL_RESPONSE / NOT_RETRYABLE`, and `502 / upstream_invalid_response`. Diagnostic codes may name a vendor because operators need evidence. Public codes must remain stable if Harbor replaces that vendor.

## Architecture

See the [normal and failure paths](../diagrams/vendor-failure-handling.md). Clients classify transport, HTTP, SOAP, and decoding details. Adapters contain those types and emit only `IntegrationFailure`. Application operation context distinguishes a missing member from a missing account without putting routing or HTTP knowledge in the failure category.

| Failure origin | Harbor category | Retry? | Public behavior |
|---|---|---:|---|
| Northstar timeout | `TIMEOUT` | `RETRYABLE` | 504 `upstream_timeout` |
| Northstar connection failure | `TEMPORARY_UNAVAILABLE` | `RETRYABLE` | 503 `service_temporarily_unavailable` |
| Customer missing | `NOT_FOUND` | `NOT_RETRYABLE` | 404 `member_not_found` |
| Heritage account missing | `NOT_FOUND` | `NOT_RETRYABLE` | 404 `account_not_found` |
| HTTP 500 or CORE_ERROR fault | `EXTERNAL_ERROR` | `UNKNOWN` | 503 `service_temporarily_unavailable` |
| Malformed or incomplete JSON/XML | `INVALID_EXTERNAL_RESPONSE` | `NOT_RETRYABLE` | 502 `upstream_invalid_response` |
| Unknown product, status, or currency | `UNSUPPORTED_EXTERNAL_VALUE` | `NOT_RETRYABLE` | 502 `upstream_invalid_response` |

These retry dispositions are laboratory rules. `UNKNOWN` acknowledges that an external error needs more policy or downstream guidance.

## Implementation

`IntegrationFailureCategory` owns generic meaning, while `RetryDisposition` makes policy visible. `IntegrationFailure` contains only a safe summary, external-system label, operation, and stable diagnostic code—never a raw payload, balance, credential, or member name. `NorthstarFailureTranslator` and `HeritageFailureTranslator` keep mappings explicit. `IntegrationFailureApiMapper` adds HTTP concerns after the application boundary. Unsupported upstream values use 502, not 400, because the API caller did not supply the invalid value. Upstream timeout consistently uses 504.

No retries, sleeps, circuit breakers, queues, workers, or production logging subsystem are introduced.

## Run the laboratory

```bash
./bin/digital-banking-lab failure northstar-timeout
./bin/digital-banking-lab failure heritage-malformed-xml
./bin/digital-banking-lab failure-matrix
```

Every supported scenario is instantaneous, offline, and repeatable. The report is operator/laboratory output and is never returned by the member API.

## What to observe

Compare `Harbor failure category` with `Diagnostic code`. The first supports application decisions across vendors; the second preserves specific engineering evidence. Notice that the same `NOT_FOUND` category becomes either `member_not_found` or `account_not_found` according to operation. Public responses omit vendor names, SOAP fault codes, stack traces, raw JSON/XML, and diagnostic codes.

## Engineering tradeoffs

`RETRYABLE` does **not** mean “retry immediately in a loop.” Safe retry depends on idempotency, operation type, attempt count, downstream guidance, latency budget, and user experience. A malformed payload will generally repeat, while a timeout may hide an operation that actually completed. Chapter 8 classifies; it does not automate recovery. A production implementation would also attach correlation identifiers and send minimized structured records to controlled telemetry.

## Automated tests

The dependency-free suite verifies every category and retry rule; all Northstar and Heritage mappings; adapter containment; contextual 404, 502, 503, and 504 mappings; absence of vendor and sensitive details from public errors; deterministic diagnostics and matrix output; and unchanged success contracts.

```bash
php tests/run.php
```

## Exercise: 429 Too Many Requests

Revisit Chapter 5 and decide:

1. Which Harbor category should HTTP 429 map to?
2. Is it retryable?
3. Should `Retry-After` affect the decision?
4. Should the Member domain know about rate limits?
5. Should Harbor expose `NORTHSTAR_RATE_LIMITED` publicly?
6. What HTTP status should Harbor return?
7. Would automatic retry always be safe?
8. Which tests prove the mapping and prevent leakage?

Do not implement automatic retries. Explain how operation context and latency budget could change your answer.
