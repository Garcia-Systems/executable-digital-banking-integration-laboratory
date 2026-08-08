# Chapter 10: SQL for Digital Banking

## Educational question

**How should Harbor retrieve and analyze relational banking data with SQL while preserving clear boundaries between database rows, application models, and business meaning?**

## Learning objectives

Identify tables, rows, primary and foreign keys; write parameterized selects, joins, filters, aggregates, and explicit date predicates; distinguish `INNER JOIN` from `LEFT JOIN`; map stored vocabulary into Harbor values; inspect a plan; and test all behavior without real time.

## Banking concept

Not all application data comes from vendor APIs. Harbor may own application state, operational records, content, preferences, audit-supporting metadata, and activity projections. The three fictional tables here are such an **operational projection—not the core banking ledger and not a complete transaction history or balance source**. `members` identifies Harbor members; `accounts` records Harbor-owned account identity and ownership; `account_activity` supplies deliberately small teaching events. Northstar and Heritage remain external and unchanged.

## Engineering concept

A primary key identifies a row; a foreign key makes ownership explicit. An inner join requires a related row, while a left join preserves the left row (used by the most-recent query to preserve accounts with no activity). Predicates select sets, aggregates such as `MAX` and `SUM` reduce them, and `ORDER BY` makes output repeatable. Parameters keep values separate from SQL syntax. Dates are ISO-8601 UTC strings and cutoffs come from the fixed application clock—not `CURRENT_TIMESTAMP`.

SQL describes matching rows, but a business question may concern the existence of a whole set. That difference is central here.

## Architecture

See the [SQL data-access diagram](../diagrams/sql-data-access.md). Application code asks the focused `MemberActivityRepository` meaningful questions. The adapter owns PDO and raw SQL. Results cross an explicit mapping boundary:

```text
SQL row: account_type = 'CHECKING'
        ↓ row mapper
AccountType::CHECKING
        ↓
Harbor application result
```

Unsupported stored enum values throw clearly rather than leaking arbitrary strings. Harbor identifiers (`member-0001`, `account-0001`) are primary keys; vendor IDs are not.

## Implementation

`database/schema.sql` explicitly defines three tables, foreign keys, and indexes. `database/fixtures.sql` contains three fictional members, four accounts, and four fixed activities. Avery has an old and recent row; Jordan has only an old row; Casey has no activity.

The adapter implements parameterized account lookup, activity since a cutoff (ordered by instant then ID), `MAX(occurred_at)` grouped only by account ID, correct inactivity through `NOT EXISTS`, and integer `SUM(amount_minor_units)`. The nullable activity amount is a teaching projection, not a ledger balance. Deterministic ordering matters because relational sets otherwise promise no presentation order and tests or pages can vary.

### Why “old activity exists” is not the same as “recent activity does not exist”

Suppose a member has old activity `2025-01-01`, recent activity `2026-01-01`, and cutoff `2025-07-19`. `activity < :cutoff` matches the old row, so a naive joined query returns the member. But “has this member had no activity since the cutoff?” is false: the recent row exists. `NOT EXISTS (recent activity)` expresses the business question and also naturally includes members with no activity. The executable pitfall encodes this counterexample.

### Index and query-plan awareness

`accounts.member_id` supports ownership lookup. `account_activity.account_id` supports timelines and correlated account lookup. `account_activity.occurred_at` can help time filtering. An index is not automatically beneficial: selectivity, table size, join order, and the planner matter. Use `explain inactive-members` and inspect scans, index searches, and correlated lookups rather than asserting version-specific plan text.

### SQLite and MySQL

The executable lab uses in-memory SQLite for deterministic, dependency-free execution; the job concept remains SQL/MySQL. These core selects, joins, aggregates, parameters, and ISO timestamps translate naturally. MySQL differs in date functions, planner output (`EXPLAIN` rather than SQLite's `EXPLAIN QUERY PLAN`), typing/coercion behavior, DDL syntax, and sometimes index choices. Production schema types would likely use native temporal types. This lab avoids dialect date arithmetic by calculating the cutoff in PHP.

## Run the laboratory

```bash
./bin/digital-banking-lab db-members
./bin/digital-banking-lab account-activity account-0001
./bin/digital-banking-lab inactive-members --days=180
./bin/digital-banking-lab sql-example inactive-members
./bin/digital-banking-lab sql-pitfall inactivity
./bin/digital-banking-lab explain inactive-members
```

## What to observe

The as-of instant is always `2026-01-15T14:30:00Z`; 180 days produces `2025-07-19T14:30:00Z`. Jordan and Casey are inactive. Avery is absent even though the incorrect query finds Avery's old row. SQL output shows placeholders, never interpolated input.

## Engineering tradeoffs

SQLite removes service setup but cannot teach every MySQL type or optimizer detail. A focused repository avoids both a generic query escape hatch and a large ORM. The projection is intentionally too small for ledger, accounting, compliance, or production audit use. Separate compound indexes might outperform the teaching indexes under real workloads; plans and measured workload should decide.

## Automated tests

The dependency-free suite checks schema and foreign keys, fixed fixtures, mapping and corrupt values, ordering, cutoff changes, aggregate integer money, the inactivity counterexample, injection-looking identifiers, the repository/application boundary, and all Chapter 0–9 regressions. It does not compare brittle full query-plan text.

## Exercise

Find members whose most recent activity is older than 90 days. First define exact business meaning; calculate the cutoff from the fixed clock; choose `MAX`/`GROUP BY`, `NOT EXISTS`, or another clear pattern; explicitly document whether members with no activity count; parameterize the cutoff; add deterministic tests; and inspect the plan. Do not rely on database “today.”

Reasoning question: **Why is `WHERE occurred_at < :cutoff` alone insufficient?** Do not implement the solution until the inclusion policy and set-level question are precise.
