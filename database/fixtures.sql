INSERT INTO members VALUES
('member-0001', 'Avery Morgan', 'ACTIVE', '2024-01-10T09:00:00Z'),
('member-0002', 'Jordan Lee', 'ACTIVE', '2024-02-20T09:00:00Z'),
('member-0003', 'Casey Rivera', 'RESTRICTED', '2024-03-30T09:00:00Z');

INSERT INTO accounts VALUES
('account-0001', 'member-0001', 'Everyday Checking', 'CHECKING', 'OPEN'),
('account-0002', 'member-0001', 'Primary Savings', 'SAVINGS', 'OPEN'),
('account-0003', 'member-0002', 'Jordan Checking', 'CHECKING', 'OPEN'),
('account-0004', 'member-0003', 'Casey Savings', 'SAVINGS', 'CLOSED');

-- A teaching activity projection, not a transaction ledger.
INSERT INTO account_activity VALUES
('activity-0001', 'account-0001', 'DEPOSIT', '2025-01-01T10:00:00Z', 10000),
('activity-0002', 'account-0001', 'CARD_PURCHASE', '2025-12-20T10:00:00Z', -4215),
('activity-0003', 'account-0001', 'DEPOSIT', '2026-01-05T15:30:00Z', 50000),
('activity-0004', 'account-0003', 'CARD_PURCHASE', '2025-06-01T12:00:00Z', -2500);
