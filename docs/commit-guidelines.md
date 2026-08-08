# Reviewable commits

Use a feature branch from `main`. Make small, coherent commits whose imperative subject explains intent: `Add transfer preview validation`, `Map ClearVerify review status`, `Fix inactivity query semantics`, or `Prevent stale member responses`.

A poor commit named `updates` might mix a SQL refactor, CSS change, vendor-adapter rewrite, and README correction. A reviewable plan instead could be:

1. Add Harbor verification status mapping.
2. Integrate verification into transfer preview.
3. Render verification state in Member Web.
4. Document the fintech workflow.

This is not a one-file-per-commit rule. Keep behavior, its tests, and the documentation needed to understand it together.

## Diff-first routine

Before committing, run `git status` and `git diff`; inspect what will enter the commit with `git diff --staged`. Before review, run `git diff main...HEAD`. Look for unexpected or generated files, credentials, public contracts, migrations, and missing tests—do not rely only on an IDE badge.

The repository ignores dependency trees, build/coverage output, local databases, `.env` files, and common IDE state. Never commit real member data or credentials.
