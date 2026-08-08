# Full-stack debugging worksheet

**Core principle:** Start with the observable symptom. Trace boundaries until expected behavior diverges from actual behavior. Evidence is an observation; a guess is not evidence.

Use this sequence: **reproduce → define expected → capture actual → find the first failing boundary → form one narrow hypothesis → inspect evidence → fix the smallest responsible layer → add a regression test → run wider validation.** Do not start by reading random files or changing code.

## 1. Symptom
What does the user or operator observe? Record the message, missing value, or wrong account without proposing a cause.

## 2. Expected behavior
What observable result and contract should this operation produce?

## 3. Reproduction
Record the exact deterministic command, test, or request and fixture. Confirm a second run behaves identically.

## 4. Boundary trace
For each applicable boundary—**Browser → API → Controller → Application → Repository/Port → Adapter → Client → External fixture**—record what it received, what it should produce, and whether it did.

## 5. First divergence
Where does expected behavior first differ from actual behavior? Stop narrowing here.

## 6. Hypothesis
Write one falsifiable statement about that boundary.

## 7. Evidence
Record evidence that supports or rejects the hypothesis:

| Evidence class | Safe observations |
|---|---|
| User-visible | UI message, missing value, wrong account |
| HTTP | method, route, status, Harbor-safe JSON |
| Application | service, Harbor IDs, workflow outcome |
| Integration | external system, operation, stable diagnostic code, response classification |
| Database | parameters, selected fixture rows, result rows, query plan |
| Frontend | selected member, active request ID, request payload, parser outcome, state transitions |

Do not dump credentials, transfer memos, whole member records, or complete vendor payloads.

## 8. Fix
What is the smallest change at the responsible layer? Do not remove a validator to conceal contract drift.

## 9. Regression test
What deterministic automated test would have caught this exact defect?

## 10. Wider validation
Run the focused test first, then unit, integration, frontend, architecture, security, and API exposure checks as applicable.

## Boundary-specific checklists

- **Browser:** inspect Network method, payload, status, JSON, selected member, active request, and state transition ordering.
- **REST:** method → path → safe headers → status → JSON syntax → required fields → vendor enum → Harbor translation.
- **SOAP:** request → Envelope → Body → operation → transport → SOAP Fault check → XML structure → Heritage model → Harbor translation. HTTP 200 is not SOAP success.
- **SQL:** state the business question; inspect fixture rows; run with explicit parameters; inspect results; verify joins and predicates. Use `EXPLAIN` only after proving correctness.
- **Money:** compare input text, expected integer minor units, and captured request before blaming Harbor arithmetic.
- **Contracts:** compare actual backend JSON, shared Chapter 19 fixture, and frontend runtime validator. HTTP success is not contract success.

Logging more is not automatically better debugging. Prefer operation, layer, stable code, request state, and justified Harbor identifiers.
