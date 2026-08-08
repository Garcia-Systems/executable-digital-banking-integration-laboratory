import { readFileSync } from 'node:fs';
import { describe, expect, it } from 'vitest';
import { parseMemberSummary, parseMemberVerification, parseTransferPreview } from '../src/contracts';

const fixture = (name: string): unknown => JSON.parse(
  readFileSync(new URL(`../../../contracts/api/${name}.json`, import.meta.url), 'utf8')
);

describe('shared Harbor API contract artifacts', () => {
  it('accepts the backend member-summary contract', () =>
    expect(parseMemberSummary(fixture('member-summary')).memberId).toBe('member-0001'));
  it('accepts the backend verification contract', () =>
    expect(parseMemberVerification(fixture('verification')).status).toBe('verified'));
  it('accepts the backend transfer-preview contract', () =>
    expect(parseTransferPreview(fixture('transfer-preview')).previewId).toBe('preview-0001'));
});
