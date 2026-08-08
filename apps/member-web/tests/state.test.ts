import { describe, expect, it, vi } from 'vitest';
import type { MemberApi } from '../src/api';
import { MemberPage } from '../src/state';
import { memberFixture } from './fixture';

describe('MemberPage state', () => {
  it('starts loading', () => expect(new MemberPage({ getMember: vi.fn() }, 'member-0001').state).toEqual({ kind: 'loading' }));
  it('becomes loaded after success', async () => {
    const page = new MemberPage({ getMember: vi.fn().mockResolvedValue(memberFixture) }, 'member-0001');
    await page.load();
    expect(page.state).toMatchObject({ kind: 'loaded', member: memberFixture });
  });
  it('becomes error after failure', async () => {
    const page = new MemberPage({ getMember: vi.fn().mockRejectedValue(new Error('failure')) }, 'member-0001');
    await page.load();
    expect(page.state.kind).toBe('error');
  });
  it('retry clears stale data and returns to loading immediately', async () => {
    let resolve!: (value: typeof memberFixture) => void;
    const pending = new Promise<typeof memberFixture>(done => { resolve = done; });
    const api: MemberApi = { getMember: vi.fn().mockResolvedValueOnce(memberFixture).mockReturnValueOnce(pending) };
    const page = new MemberPage(api, 'member-0001');
    await page.load();
    const retry = page.retry();
    expect(page.state).toEqual({ kind: 'loading' });
    resolve(memberFixture);
    await retry;
  });
});
