# Harbor code-review checklist

Use this checklist to guide—not replace—engineering judgment. Start with the diff and the stated intent.

## Correctness
- Does behavior match the requirement? Are boundary conditions tested?
- Are `Money` and date semantics correct?

## Architecture
- Are Harbor-owned ports preserved? Did vendor terminology leak upward?
- Is orchestration in application services and presentation logic separated?

## API contract
- Is the change backward-compatible and are shared fixtures updated intentionally?
- Does Member Web still parse the contract?

## Data and security
- Is new data required? Are secrets and vendor IDs hidden?
- Are diagnostics minimized and trust boundaries still validated?

## Database
- Is SQL parameterized and does it answer the business question?
- Are schema changes migrated and indexes justified?

## Frontend
- Are loading, error, stale, mobile, and accessibility states preserved?
- Is dynamic data rendered safely?

## Testing
- Are unit tests placed at useful boundaries and integration seams tested?
- Does a defect include a behavioral regression test rather than only interaction mocks?

## Documentation
- Does the relevant chapter explain the change?
- Are commands, examples, diagrams, and deployment notes accurate?
