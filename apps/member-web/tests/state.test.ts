import { describe, expect, it, vi } from 'vitest';
import { NetworkError, RequestAbortedError, type MemberApi } from '../src/api';
import type { MemberSummaryDto } from '../src/contracts';
import { MemberPage, RequestSequence, reduceMemberRequestState } from '../src/state';
import { memberFixture } from './fixture';

type DeferredRequest = {
  memberId: string;
  signal?: AbortSignal;
  resolve: (member: MemberSummaryDto) => void;
  reject: (error: unknown) => void;
};

class DeferredHarborApiClient implements MemberApi {
  readonly requests: DeferredRequest[] = [];
  getMember(memberId: string, signal?: AbortSignal): Promise<MemberSummaryDto> {
    return new Promise((resolve, reject) => this.requests.push({ memberId, signal, resolve, reject }));
  }
}

describe('request state model', () => {
  it('generates stable monotonic identities for every fresh sequence', () => {
    const sequence = new RequestSequence();
    expect([sequence.next(), sequence.next()]).toEqual(['request-0001', 'request-0002']);
    expect(new RequestSequence().next()).toBe('request-0001');
  });

  it('starts in idle and records request metadata while loading', () => {
    const page = new MemberPage(new DeferredHarborApiClient(), 'member-0001');
    expect(page.state).toEqual({ kind: 'idle' });
    void page.load();
    expect(page.state).toEqual({ kind: 'loading', requestId: 'request-0001', requestedMemberId: 'member-0001' });
  });

  it('loads matching member data and represents empty accounts explicitly', async () => {
    const full = new MemberPage({ getMember: vi.fn().mockResolvedValue(memberFixture) }, 'member-0001');
    await full.load();
    expect(full.state).toMatchObject({ kind: 'loaded', requestId: 'request-0001', member: memberFixture });
    const empty = new MemberPage({ getMember: vi.fn().mockResolvedValue({ ...memberFixture, accounts: [] }) }, 'member-0001');
    await empty.load();
    expect(empty.state.kind).toBe('empty');
  });

  it('aborts the obsolete request and ignores its late success', async () => {
    const api = new DeferredHarborApiClient();
    const page = new MemberPage(api, 'member-0001');
    const first = page.load();
    const second = page.selectMember('member-0002');
    expect(api.requests[0].signal?.aborted).toBe(true);
    expect(api.requests[1].signal?.aborted).toBe(false);
    api.requests[1].resolve({ ...memberFixture, memberId: 'member-0002', name: 'Jordan Lee' });
    await second;
    api.requests[0].resolve(memberFixture);
    await first;
    expect(page.state).toMatchObject({ kind: 'loaded', requestedMemberId: 'member-0002', member: { memberId: 'member-0002' } });
    expect(page.trace.entries).toEqual([
      'request-0001 START member-0001', 'request-0001 ABORT', 'request-0002 START member-0002',
      'request-0002 SUCCESS', 'request-0001 STALE_SUCCESS_IGNORED'
    ]);
  });

  it('ignores both late failures and aborted current requests without showing an error', async () => {
    const api = new DeferredHarborApiClient();
    const page = new MemberPage(api, 'member-0001');
    const first = page.load();
    const second = page.selectMember('member-0002');
    api.requests[0].reject(new NetworkError());
    await first;
    expect(page.state.kind).toBe('loading');
    api.requests[1].reject(new RequestAbortedError());
    await second;
    expect(page.state.kind).toBe('loading');
    expect(page.trace.entries).toContain('request-0001 STALE_FAILURE_IGNORED');
  });

  it('clears loaded financial data immediately when selection changes', async () => {
    const api = new DeferredHarborApiClient();
    const page = new MemberPage(api, 'member-0001');
    const first = page.load();
    api.requests[0].resolve(memberFixture);
    await first;
    void page.selectMember('member-0002');
    expect(page.state).toEqual({ kind: 'loading', requestId: 'request-0002', requestedMemberId: 'member-0002' });
    expect('member' in page.state).toBe(false);
    expect(page.activeRequestId).toBe('request-0002');
  });

  it('user retry creates a new request and returns an error page to loading', async () => {
    const api = new DeferredHarborApiClient();
    const page = new MemberPage(api, 'member-0001');
    const failed = page.load();
    api.requests[0].reject(new NetworkError());
    await failed;
    expect(page.state).toMatchObject({ kind: 'error', requestId: 'request-0001', error: expect.any(NetworkError) });
    const retry = page.retry();
    expect(page.state).toEqual({ kind: 'loading', requestId: 'request-0002', requestedMemberId: 'member-0001' });
    api.requests[1].resolve(memberFixture);
    await retry;
    expect(page.state.kind).toBe('loaded');
  });

  it('pure reducer ignores stale success and failure events', () => {
    const loading = { kind: 'loading', requestId: 'request-0002', requestedMemberId: 'member-0002' } as const;
    const success = { type: 'REQUEST_SUCCEEDED', requestId: 'request-0001', requestedMemberId: 'member-0001', member: memberFixture } as const;
    const failure = { type: 'REQUEST_FAILED', requestId: 'request-0001', requestedMemberId: 'member-0001', error: new Error() } as const;
    expect(reduceMemberRequestState(loading, success)).toBe(loading);
    expect(reduceMemberRequestState(loading, failure)).toBe(loading);
    expect(loading).toEqual({ kind: 'loading', requestId: 'request-0002', requestedMemberId: 'member-0002' });
  });
});
