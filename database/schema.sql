PRAGMA foreign_keys = ON;

CREATE TABLE members (
    member_id TEXT PRIMARY KEY,
    display_name TEXT NOT NULL,
    membership_status TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE TABLE accounts (
    account_id TEXT PRIMARY KEY,
    member_id TEXT NOT NULL,
    display_name TEXT NOT NULL,
    account_type TEXT NOT NULL,
    account_status TEXT NOT NULL,
    FOREIGN KEY (member_id) REFERENCES members(member_id)
);

CREATE TABLE account_activity (
    activity_id TEXT PRIMARY KEY,
    account_id TEXT NOT NULL,
    activity_type TEXT NOT NULL,
    occurred_at TEXT NOT NULL,
    amount_minor_units INTEGER NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id)
);

-- Ownership lookup, per-account timeline lookup, and time-cutoff filtering.
CREATE INDEX idx_accounts_member_id ON accounts(member_id);
CREATE INDEX idx_activity_account_id ON account_activity(account_id);
CREATE INDEX idx_activity_occurred_at ON account_activity(occurred_at);
