# Chapter 20: Debugging the Full Stack

## Educational question

**How should an engineer trace a digital-banking symptom across browser, API, application, integration, and database boundaries until the actual failing layer is identified?**

The answer is not “read random files until something looks suspicious.” **Start with the observable symptom. Trace boundaries until expected behavior diverges from actual behavior.**

## Learning objectives

By the end, you can reproduce a report deterministically; separate symptom from cause; trace Harbor identifiers and safe diagnostics; inspect HTTP, JSON, XML, SQL, frontend state, and orchestration; distinguish transport, decoding, semantic translation, policy, presentation, and data-logic failures; form a narrow hypothesis; and prove a fix with regression and wider tests.

## Banking concept

A member experiences one application even when a journey crosses many systems. “Why can’t I see my account?” may involve browser state, Harbor HTTP, PHP orchestration, Northstar REST, or vendor translation. “Why is my available balance missing?” may originate in Heritage SOAP. The visible layer reports the symptom; it is not automatically responsible.

**Symptoms propagate upward. Causes originate somewhere specific.** Invalid Northstar JSON becomes a decode failure, then a Harbor `IntegrationFailure`, then HTTP 502, then “We couldn’t load your accounts.” The useful engineering question is where expected behavior first stopped matching its contract.

## Engineering concept

Use the same method every time:

1. Reproduce.
2. Define expected behavior.
3. Capture actual behavior.
4. Identify the first failing boundary.
5. Form a narrow, falsifiable hypothesis.
6. Inspect scoped evidence.
7. Fix the smallest responsible layer.
8. Add or strengthen a regression test.
9. Re-run broader validation.

This is a search-space reduction technique. If Member Web says “Unable to load accounts,” call Harbor directly. A failing Harbor response makes browser rendering unlikely to be the first divergence. Continue through the service, adapter, client, and response classification; stop at the first mismatch.

### Debugging ladder

1. UI
2. Harbor HTTP/controller
3. Application service
4. Harbor port
5. Adapter
6. Client
7. Transport
8. External fixture
9. Database, where applicable

At each step ask: **What did this layer receive? What was it expected to produce? Did it?**

## Architecture

The complete path is:

```text
Member Web → Harbor REST API → Controller → Application Service
                                      ├─ Harbor Database
                                      ├─ Northstar Adapter → Northstar REST
                                      ├─ Heritage Adapter  → Heritage SOAP
                                      └─ ClearVerify Adapter → ClearVerify REST
```

See the [full-stack debugging diagram](../diagrams/full-stack-debugging.md). Evidence feeds a hypothesis; a supported hypothesis leads to the smallest fix and a regression test.

## Implementation

`DebugScenario` records an ID, title, observable symptom, journey, one fault component, expected diagnosis, deterministic setup, safe evidence, first divergence, scoped detail, and regression guard. `DebugScenarioCatalog` fixes IDs and ordering. `DebugApplicationFactory` is a separate outer composition root: selecting a scenario returns a fresh immutable fixture with exactly one replacement. Normal `HttpKernelFactory` and `LaboratoryApplicationFactory` are untouched.

This is controlled fault injection, not product behavior. There are no query-string “break everything” switches, randomness, sleeps, live outages, or debug branches scattered through normal code.

### Scenario catalog

| ID | Observable journey symptom | Injected fixture boundary | Correct regression guard |
|---|---|---|---|
| `frontend-stale-member` | member-0002 is replaced by member-0001 | request coordinator | stale completion test |
| `api-contract-drift` | HTTP 200, UI load failure | API fixture | shared contract fixture |
| `northstar-new-product-class` | summary unavailable | Northstar semantic response | enum allow-list test |
| `northstar-malformed-json` | summary unavailable | Northstar response syntax | decoder test |
| `heritage-soap-fault` | balances unavailable | SOAP operation | Fault-before-success test |
| `heritage-malformed-xml` | balances unavailable | Heritage XML | malformed XML test |
| `sql-inactivity-predicate` | active member listed inactive | query semantics | old + recent activity test |
| `sql-missing-never-active-member` | never-active member disappears | JOIN predicate | never-active report test |
| `transfer-verification-review` | valid form workflow blocked | ClearVerify policy outcome | manual-review policy test |
| `money-decimal-conversion` | 12.34 becomes 1233 | frontend parser fixture | exact minor-units test |
| `public-vendor-id-leak` | external ID appears publicly | presenter fixture | API exposure test |
| `orchestration-account-mismatch` | checking shows savings balance | orchestration fixture | account identity test |

The catalog command deliberately hides the fault and diagnosis. `debug-run` shows only learner-visible and boundary-safe evidence. `debug-trace` reveals PASS/FAIL boundaries and the first divergence. Only `debug-detail` reveals narrowly controlled engineering details—never credentials, complete vendor payloads, full financial records, or transfer memos.

### Evidence taxonomy

- **User-visible:** UI message, missing value, wrong account.
- **HTTP:** method, route, status, Harbor-safe JSON.
- **Application:** invoked service, Harbor IDs, workflow outcome.
- **Integration:** system, operation, stable diagnostic, response classification.
- **Database:** explicit parameters, relevant fixture and result rows, later a query plan.
- **Frontend:** selected member, active request ID, payload, runtime parser outcome, state transitions.

Evidence is observed. “Northstar is down” is speculation until transport evidence supports it. Chapter 17's minimization remains active: operation and stable code usually teach more safely than arbitrary payload dumps.

### Browser and contract debugging

Use any browser's generic Network and state tools; no browser-specific screenshots are required. Inspect selected member and active request before and after each completion. For money, inspect the request payload: input `12.34` should become integer `1234`. If it is `1233`, the first divergence precedes the network request.

For a contract failure, compare three artifacts: actual backend JSON, the Chapter 19 shared fixture, and the frontend runtime validator. Do not remove the validator to make the error disappear. HTTP 200 proves transport-level success, not frontend contract success.

### REST and SOAP debugging

For Northstar and ClearVerify trace method → path → safe headers → status → JSON syntax → required fields → vendor enum → Harbor translation. A 200 response can contain malformed JSON; valid JSON can carry an unsupported semantic value. Those are different first divergences.

For Heritage trace SOAP request → Envelope → Body → operation → transport → **SOAP Fault?** → XML structure → Heritage model → Harbor translation. Check a Fault before assuming a success body. HTTP 200 with a valid SOAP Fault is an operation failure; HTTP 200 with invalid XML is a decoding failure. Similar member symptoms do not imply identical causes.

### SQL debugging

1. State the business question in plain English.
2. Inspect the relevant fixture rows.
3. Run SQL with explicit parameters.
4. Inspect returned rows.
5. Ask whether SQL answers the question.
6. Inspect joins and predicates.
7. Use `EXPLAIN` only after correctness is understood.

A database can execute a query perfectly while the query answers the wrong question. `activity.occurred_at < cutoff` finds an old row; it does not establish the absence of a recent row. `NOT EXISTS(recent activity)` expresses that absence. Likewise, a nullable right-table predicate in `WHERE` can turn a `LEFT JOIN` into inner-join behavior and remove a never-active member.

## Run the laboratory

```bash
./bin/digital-banking-lab debug-scenarios
./bin/digital-banking-lab debug-run api-contract-drift
./bin/digital-banking-lab debug-trace api-contract-drift
./bin/digital-banking-lab debug-detail api-contract-drift
php tests/Debug/run.php
```

Re-running a scenario produces byte-stable output. Unknown IDs fail explicitly. Use the reusable [debugging workflow worksheet](../docs/debugging-workflow.md) rather than modifying code before reproduction.

## What to observe

Compare `northstar-new-product-class` with `northstar-malformed-json`: transport succeeds in both, parsing succeeds only in the former, and semantic translation then rejects the new product class. Compare `heritage-soap-fault` with `heritage-malformed-xml`: their safe HTTP symptoms may resemble each other, while their first failing boundaries differ.

`transfer-verification-review` also demonstrates that a failed form workflow is not necessarily input validation. The form and request pass; Harbor's verification policy correctly blocks a translated `REVIEW_REQUIRED` result.

## Worked example: HTTP 200 but Member Web fails

**Observed:** Member Web shows a load error.

**Hypothesis 1:** Harbor API is unavailable. Reproduce with `debug-run api-contract-drift`. Network evidence is HTTP 200, so reject that hypothesis.

Inspect the JSON contract. The frontend expects `displayName`; the isolated response provides `nameText`. The runtime validator correctly rejects it. The first divergence is the API/frontend contract boundary—not routing, PHP orchestration, or Northstar.

**Smallest fix:** restore the intentional `displayName` field in the faulty producer. Do not loosen the validator. **Regression:** verify one shared member-summary fixture against both backend production and frontend parsing.

## Worked example: SQL inactivity predicate

**Observed:** a recently active member is listed inactive. The fixture has both an old and a recent activity row. The incorrect SQL selects the old row, but the business question asks whether *no recent row exists*. The first divergence is query semantics. Replace the row predicate with `NOT EXISTS` and retain a regression member having old + recent activity.

## Engineering tradeoffs

The laboratory's traces are curated and therefore more legible than production telemetry. They teach boundary reasoning without claiming observability completeness. A single fault per scenario improves diagnosis but does not simulate every compound incident. Explicit fixtures cost maintenance, yet preserve repeatability and prevent Chapter 0–19 behavior from being contaminated.

More logging is not automatically better debugging. Useful facts include layer, operation, response class, stable diagnostic code, request state, and justified Harbor IDs. Avoid credentials, raw member financial records, whole vendor payloads, and arbitrary memo text.

## Automated tests

The debug suite verifies stable unique IDs and order, required metadata, observable deterministic output, exactly one named replacement, expected first divergence, unknown-ID behavior, isolation/reset between scenarios, scoped diagnostics, and normal public API behavior. Existing unit, integration, frontend, architecture, security, and API exposure checks remain the broader regression net.

## Exercise: the missing cent mystery

A member enters `100.00`. Member Web displays **Projected available balance: $2,285.74**; expected is **$2,285.75**.

Evidence supplied:

```text
Selected source: account-0001
Input text: 100.00
Request: amount = { currency: "USD", minorUnits: 10000 }
API response: sourceAvailableBalance.minorUnits = 238575
API response: projectedAvailableBalance.minorUnits = 228574
Presenter input: projectedAvailableBalance.minorUnits = 228574
Presenter output: $2,285.74
Trace: frontend parse PASS; request validation PASS; Heritage balance association PASS
```

Do not guess. Determine: (1) frontend conversion, Harbor `Money` arithmetic, Heritage available balance, or presenter formatting? (2) At which boundary does expected first diverge? (3) Which single fixture fact supports that conclusion? (4) What regression test would prevent recurrence? The evidence contains exactly one inconsistent boundary; the solution is intentionally not printed here.

## Chapter conclusion

A reliable debugger fixes causes rather than hiding symptoms. Reproduce first, define the contract at every boundary, stop at the first divergence, prove one hypothesis with minimized evidence, make the smallest change, and let focused plus broad regression suites establish confidence. Chapter 21 will apply that discipline to **Git, code review, and deployment-readiness workflows**; it is not implemented here.
