# Chapter 12: Building the Member Web Experience

## Educational question

**How should Harbor build a member-facing web experience that consumes Harbor-owned APIs without exposing vendor complexity to the browser?**

## Learning objectives

After this chapter, you can structure a small HTML/CSS/TypeScript application; fetch and validate Harbor JSON; model the public API explicitly; render member and account information; represent loading, success, empty, and error states; design mobile-first; add basic accessibility; and explain which logic belongs in presentation versus the backend.

## Banking concept

Members experience digital channels, not SOAP, SQL, adapters, or PHP application-service classes. They see their name, accounts, balances, statuses, and eventually actions. Member Web presents those Harbor concepts coherently while backend systems absorb integration complexity. Chapter 12 is intentionally read-only: it adds neither transfers nor mutations.

The application always requests fictional `member-0001`. This is a visible **teaching shortcut**, not login, authorization, identity proofing, or member selection. Production software must derive the member context from a properly authenticated and authorized session.

## Engineering concept

The complete boundary chain is:

```text
Northstar / Heritage / Harbor DB
              ↓
        Harbor backend
              ↓
        Harbor REST API
              ↓
     TypeScript API client
              ↓
        Frontend state
              ↓
             DOM
```

The browser calls only `GET /api/members/member-0001`. `HarborApiClient` owns URL construction, `fetch`, HTTP handling, JSON decoding, and runtime parsing. It does not render. `MemberPage` owns a discriminated request state. Rendering maps that state to semantic DOM without performing banking decisions.

### Why not call the vendor from the browser?

Direct calls would expose secrets and architecture, couple a public experience to vendor identifiers and availability, encounter vendor CORS restrictions, duplicate translation and business rules, and prevent Harbor from centralizing security and safe failure translation. This is an architectural boundary rather than a claim that browsers are inherently unsafe: the public channel consumes the stable capability Harbor chooses to publish.

### API DTO versus UI model

`MemberSummaryDto`, `AccountSummaryDto`, and `MoneyDto` restate Harbor's public contract in TypeScript; they are not generated PHP objects and never contain vendor DTOs. Interfaces alone cannot validate JSON, so `parseMemberSummary` checks every required field and integer `minorUnits` before data reaches the UI. For this small screen, parsed DTOs plus focused enum-label formatting are sufficient. A separate view-model mapping layer would add ceremony without new meaning.

The formatted balance comes from the API. Member Web displays `balance.formatted`; it performs no floating-point financial arithmetic.

## Architecture

See the [Member Web experience diagram](../diagrams/member-web-experience.md). Its most important constraint is: **the browser does not integrate with vendors directly**.

## Implementation

`apps/member-web` is an independent Vite application using browser DOM APIs and strict TypeScript—no component framework or CSS framework. The base URL uses `VITE_HARBOR_API_BASE_URL`, defaulting locally to `http://127.0.0.1:8080`; no production host is embedded.

The explicit `MemberPageState` variants are `loading`, `loaded`, `empty`, and `error`. Beginning a load replaces prior data immediately. A 404 receives stable member-not-found wording. HTTP 502, 503, and 504 receive temporary-service wording. Other failures receive a generic message. Raw bodies, codes, exceptions, and stack traces are never rendered. Retry performs one new request—there is no automatic loop.

### Loading, error, and empty states

A digital experience is not only its success screen. Loading announces progress, success displays validated content, empty says that an existing member has no digitally available accounts, and error offers a real Retry button. These mutually exclusive states prevent stale content and contradictory boolean combinations. Chapter 13 can make request-state and resilience patterns more sophisticated without changing this API boundary.

### Mobile-first and accessibility

Mobile-first does not mean shrinking a desktop design. The base layout starts with one-column cards, narrow-safe dimensions, reusable spacing tokens, readable type, and touch-friendly controls. A min-width media query progressively enhances the cards into a two-column grid. This is a focused teaching layout, not a complete production design system.

The document uses `header`, `main`, `section`, `article`, and a logical heading hierarchy. Loading uses status/live semantics; errors use an alert labelled by the primary heading; balance labels are available to assistive technology; statuses include words rather than relying on color; and the button has a visible keyboard focus style. These choices are intentional basics, not a claim of complete WCAG conformance.

## Run the laboratory

Start Harbor's API from the repository root:

```bash
php -S 127.0.0.1:8080 -t public
```

In another terminal, install and start Member Web:

```bash
cd apps/member-web
npm install
npm run dev
```

Open `http://127.0.0.1:5173`. To choose another Harbor deployment for development, set `VITE_HARBOR_API_BASE_URL`. The PHP entry point permits only the known `http://127.0.0.1:5173` development origin; it does not use a wildcard. Production CORS depends on the real topology and should be configured at the HTTP edge.

## What to observe

* Loading appears before the request settles, with no stale account content.
* Avery Morgan, Active, two account cards, `$2,450.75`, and `$8,120.00` come from the Chapter 4 representation.
* Title-case labels are presentation mappings; the API retains its lowercase enums.
* Account cards stack on a narrow viewport and form a grid when space permits.
* A missing member differs from a member with no accounts.
* Errors reveal no dependency, diagnostic, or backend implementation details.

## Engineering tradeoffs

The small hand-written parser is intentionally repetitive and educational. A larger contract may justify a schema library or generated client, provided Harbor still owns and reviews the public contract. Rendering DTOs directly is reasonable here; richer presentation-specific meaning may justify view models later. The exact-origin CORS header supports two local processes with minimal backend change, but production might serve both under one origin or apply policy in a gateway.

## Automated tests

Frontend unit and DOM-style tests use Vitest and jsdom with in-memory `fetch` responses; they do not require PHP or internet access. They cover parsing, URLs and GET semantics, safe typed errors, malformed JSON, every page-state transition, stale-data removal on retry, content and currency display, empty accounts, safe 404/503 messages, semantic errors, headings, and the button. Run:

```bash
cd apps/member-web
npm test
npm run typecheck
npm run build
```

Backend regressions remain separate and run with `php tests/run.php` (or `composer test`). A manual full-stack smoke check is deliberately preferred over adding a heavy browser automation dependency: start both processes, open the page, and confirm Avery and both balances.

## Exercise

Add a small selector containing known deterministic Harbor MemberIds. Changing selection must request the corresponding Harbor endpoint, return state to loading, remove the previous member immediately, safely handle 404, and have frontend tests for selection and stale-content behavior.

Do not add authentication and do not implement transfers. Explain: **Why is selecting a Harbor MemberId acceptable in this teaching laboratory but not equivalent to production authentication?**
