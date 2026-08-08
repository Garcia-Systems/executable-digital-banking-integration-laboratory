import { describe, expect, it } from 'vitest';
import { ContractError, parseMemberSummary } from '../src/contracts';
import { memberFixture } from './fixture';

describe('Harbor member contract parser', () => {
  it('parses the complete valid representation', () => expect(parseMemberSummary(memberFixture)).toEqual(memberFixture));
  it('rejects a missing memberId', () => expect(() => parseMemberSummary({ ...memberFixture, memberId: undefined })).toThrow(ContractError));
  it('rejects a malformed accounts field', () => expect(() => parseMemberSummary({ ...memberFixture, accounts: {} })).toThrow(ContractError));
  it.each([1.25, '245075', null])('requires integer minorUnits: %s', minorUnits => {
    const malformed = structuredClone(memberFixture) as unknown as Record<string, unknown>;
    ((malformed.accounts as Record<string, unknown>[])[0].balance as Record<string, unknown>).minorUnits = minorUnits;
    expect(() => parseMemberSummary(malformed)).toThrow(ContractError);
  });
  it('rejects a malformed Money structure', () => {
    const malformed = structuredClone(memberFixture) as unknown as Record<string, unknown>;
    (malformed.accounts as Record<string, unknown>[])[0].balance = { currency: 'USD' };
    expect(() => parseMemberSummary(malformed)).toThrow(ContractError);
  });
});
