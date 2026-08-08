# Manual capstone walkthrough

## Clean checkout setup

```bash
composer install
cd apps/member-web && npm install --no-audit --no-fund && cd ../..
```

No persistent database initialization is needed: each PHP composition creates and seeds an in-memory SQLite database from `database/schema.sql` and `database/fixtures.sql`.

## Verify and run

```bash
./bin/verify
./bin/digital-banking-lab capstone-verify
./bin/digital-banking-lab run-capstone
./bin/digital-banking-lab run-capstone --trace
```

Start the API and web application in separate terminals:

```bash
php -S 127.0.0.1:8080 -t public
cd apps/member-web && npm run dev
```

Open the Vite URL (normally `http://127.0.0.1:5173`). Verify Avery Morgan and both accounts, inspect VERIFIED status, open Transfer Preview, select Everyday Checking → Primary Savings, enter `500.00`, enter `Move to savings`, and select **Preview transfer**. Confirm projected available balance `$1,885.75` and **No funds have been moved.** Repeat: the starting available balance remains `$2,385.75`.

For a deterministic failure without editing source, run:

```bash
./bin/digital-banking-lab run-capstone --scenario=verification-review
```

The provider transport succeeds with its fictional review state; Harbor translates it, blocks preview with Harbor vocabulary, and reports `EXPECTED_FAILURE`.

## Browser validation scope

The Member Web unit/runtime-contract suite uses jsdom, not a browser. A manual browser check should include narrow/mobile layout, the public `/help/transfer-preview.html` page, and the financial application's `noindex` metadata. This is intentionally not presented as automated browser end-to-end coverage.
