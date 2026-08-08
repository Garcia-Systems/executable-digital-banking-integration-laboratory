# Chapter 21: Git, Code Review, and Deployment Readiness

## Educational question

How should Harbor prepare a full-stack change so another engineer can review it confidently and the repository can prove it is ready to merge and deploy?

> **A change is not ready merely because it works on one machine.**

A reviewable change communicates what and why, affected layers and contracts, introduced risks, automated and manual evidence, and what intentionally remains unchanged.

## Learning objectives

After this chapter, you can describe a practical Git workflow, isolate coherent changes, inspect a diff, select tests from risk, review architecture/API/data consequences, verify documentation, explain readiness gates, and write an evidence-based summary.

## Banking concept

A small digital-banking request may cross member experience, Harbor APIs, application policy, a vendor adapter, SQL, sensitive data, and tests. Review therefore needs evidence, not code-style preference alone. Exact-balance transfer behavior, for example, is a financial policy boundary even if its diff is one character.

## Engineering concept

The central model is **Diff → Risk → Evidence → Review → Readiness**:

- **Diff:** what actually changed?
- **Risk:** what behavior, boundary, contract, or data could it affect?
- **Evidence:** what proves the intended behavior?
- **Review:** what should another engineer inspect or challenge?
- **Readiness:** have every required gate and deliberate manual check passed?

Git's **working tree** is current file state; the **staging area** selects the next recorded logical **commit**. A **branch** is a line of development, a **diff** is changed content, a **merge** integrates reviewed work, and a **pull request** is a collaboration mechanism around a proposed branch—not proof by itself.

Use a simple trunk/feature model: `main` → feature branch → small coherent commits → local validation → diff review → pull-request review → merge readiness. This laboratory neither calls GitHub nor creates a pull request.

## Architecture

The [change-delivery workflow](../diagrams/change-delivery-workflow.md) puts classification and risk between a diff and its evidence. `GitRepositoryInspector` alone executes fixed Git argument arrays. `ChangeSet` applies transparent path heuristics; `ChangeRiskAssessment` explains LOW, MODERATE, or HIGH; review scenarios exercise human judgment; `DeploymentReadinessCheck` combines independent gates. None decides whether humans must merge.

| Change | Risk | Evidence |
|---|---|---|
| README typo | LOW | docs check |
| Member Web CSS layout | LOW | frontend tests + manual viewport |
| New optional Harbor API field | MODERATE | contract + HTTP + frontend tests |
| Inactivity SQL logic | MODERATE/HIGH | repository + integration tests |
| Transfer `Money` rule | HIGH | unit + integration boundary tests |
| Sensitive-data exposure | HIGH | security + API data checks |

## Implementation

`ChangeSet` recognizes domain, application, integration, HTTP, frontend, database, API-contract, testing, security/data, and documentation paths in stable order. It intentionally does not parse arbitrary code semantics. A public contract is at least MODERATE; money validation, sensitive data, and vendor-boundary work are HIGH. Reasons and required evidence are always printed; classification is heuristic, never an authoritative security review.

The immutable teaching scenarios distinguish **APPROVE** (no identified scenario blocker), **COMMENT** (clarification or improvement required), and **BLOCK** (a known correctness, security, or contract rule is violated). `review-run` withholds the verdict; `review-assess` reveals the teaching rationale.

Readiness gates cover known/clean Git state, unit/integration/frontend suites, architecture, security, API exposure, contract compatibility, documentation, migrations, and deterministic safe configuration. A schema change without a migration, a dirty tree, contract mismatch, or failing security gate is NOT READY. A docs typo may be READY with proportionate evidence.

The lightweight credential lesson is to inspect public bundles, fixtures, snapshots, and diffs for fake markers such as `laboratory-token`; approved deterministic configuration is not a production secret scanner. Generated dependencies, coverage, local databases, `.env`, and IDE files are ignored.

## Run the laboratory

```bash
./bin/digital-banking-lab delivery-status
./bin/digital-banking-lab review-impact
./bin/digital-banking-lab change-risk
./bin/digital-banking-lab review-scenarios
./bin/digital-banking-lab review-run vendor-leak
./bin/digital-banking-lab review-assess vendor-leak
./bin/digital-banking-lab deployment-readiness --scenario=failing-unit-test
./bin/digital-banking-lab release-summary
./bin/verify
```

Readiness scenarios are `dirty-working-tree`, `failing-unit-test`, `api-contract-mismatch`, `vendor-id-exposure`, `missing-migration`, and `documentation-only-typo`. Without a scenario, Git state is live while validation gates represent the documented local evidence; tests inject Git command fixtures.

## What to observe

For a transfer validation change, inspect `Money` and `PreviewTransfer` units, an exact-balance boundary, HTTP integration behavior, the shared contract, and Member Web parser/form tests. A different diff demands different evidence. Review correctness, maintainability, architecture, contracts, security, data exposure, and tests—not merely spacing and names.

Local checks give fast feedback. CI runs the same `./bin/verify` core plus architecture, security, and data checks, with deterministic transports and no vendor credentials. Teams may conceptually protect `main` with passing CI, approval, resolved comments, and an up-to-date branch; this repository configures no branch-protection API.

## Engineering tradeoffs

Path classification is predictable and teachable but cannot understand intent. Git subprocess use gives truthful local state but is isolated for fixture testing and cannot accept arbitrary command fragments. A clean tree does not prove correctness; passing tests do not detect every accidental secret, undocumented migration, or contract drift. Manual review remains essential.

Deployment readiness means defined gates passed and the change is eligible for the next delivery step. It does **not** mean production deployment succeeded, production health was verified, or rollback is unnecessary. This chapter deploys nothing.

## Automated tests

Delivery tests cover deterministic classification/order, scenario verdicts, proportional risk, mandatory gate failures, Git parsing/error behavior, fixed argument arrays, and CI commands. Existing backend, integration, frontend, architecture, security, and API-data suites protect Chapters 0–20.

## Exercise

Hypothesis: **Add `preferredName` to Harbor's member API and Member Web.** Produce:

1. a proposed commit plan;
2. changed layers;
3. API contract impact;
4. data-sensitivity classification;
5. backend tests;
6. frontend tests;
7. shared-contract fixture update;
8. documentation update;
9. risk classification;
10. focused review-checklist items;
11. deployment-readiness evidence.

Would it be ready if PHP tests pass but the frontend contract test fails? Explain your gate decision without solving the rest of the exercise.
