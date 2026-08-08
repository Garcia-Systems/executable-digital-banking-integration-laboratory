import type { MemberSummaryDto } from '../src/contracts';

export const memberFixture: MemberSummaryDto = {
  memberId: 'member-0001', name: 'Avery Morgan', status: 'active', accounts: [
    { accountId: 'account-0001', displayName: 'Everyday Checking', type: 'checking', balance: { currency: 'USD', minorUnits: 245075, formatted: '$2,450.75' }, status: 'open' },
    { accountId: 'account-0002', displayName: 'Primary Savings', type: 'savings', balance: { currency: 'USD', minorUnits: 812000, formatted: '$8,120.00' }, status: 'open' }
  ]
};
