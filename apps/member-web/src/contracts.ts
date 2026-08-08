export interface MoneyDto {
  currency: string;
  minorUnits: number;
  formatted: string;
}

export interface AccountSummaryDto {
  accountId: string;
  displayName: string;
  type: string;
  balance: MoneyDto;
  status: string;
}

export interface MemberSummaryDto {
  memberId: string;
  name: string;
  status: string;
  accounts: AccountSummaryDto[];
}

export class ContractError extends Error {
  constructor(message = 'Harbor returned an unexpected member representation.') {
    super(message);
    this.name = 'ContractError';
  }
}

const record = (value: unknown): Record<string, unknown> => {
  if (typeof value !== 'object' || value === null || Array.isArray(value)) throw new ContractError();
  return value as Record<string, unknown>;
};

const text = (value: unknown): string => {
  if (typeof value !== 'string') throw new ContractError();
  return value;
};

export function parseMemberSummary(value: unknown): MemberSummaryDto {
  const member = record(value);
  if (!Array.isArray(member.accounts)) throw new ContractError();
  return {
    memberId: text(member.memberId),
    name: text(member.name),
    status: text(member.status),
    accounts: member.accounts.map((candidate) => {
      const account = record(candidate);
      const balance = record(account.balance);
      if (!Number.isInteger(balance.minorUnits)) throw new ContractError();
      return {
        accountId: text(account.accountId),
        displayName: text(account.displayName),
        type: text(account.type),
        balance: {
          currency: text(balance.currency),
          minorUnits: balance.minorUnits as number,
          formatted: text(balance.formatted)
        },
        status: text(account.status)
      };
    })
  };
}
