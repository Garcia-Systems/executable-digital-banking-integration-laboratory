import { describe, expect, it, vi } from 'vitest';
import { HarborApiError } from '../src/api';
import { renderMemberPage } from '../src/render';
import { MemberPage, type MemberPageState } from '../src/state';
import { memberFixture } from './fixture';

const render = (state: MemberPageState) => {
  const root = document.createElement('main');
  const page = new MemberPage({ getMember: vi.fn().mockResolvedValue(memberFixture) }, 'member-0001');
  vi.spyOn(page, 'retry').mockResolvedValue();
  renderMemberPage(root, state, page);
  return { root, page };
};
const metadata = { requestId: 'request-0001' as const, requestedMemberId: 'member-0001' };

describe('member rendering', () => {
  it('renders the complete Harbor member experience and exactly one h1', () => {
    const { root } = render({ kind: 'loaded', member: memberFixture, ...metadata });
    for (const value of ['Avery Morgan', 'Membership status:', 'Active', 'Everyday Checking', 'Primary Savings', '$2,450.75', '$8,120.00']) expect(root.textContent).toContain(value);
    expect(root.querySelectorAll('h1')).toHaveLength(1);
    expect(root.querySelectorAll('article')).toHaveLength(2);
    expect(root.innerHTML).toContain('account-0001');
  });
  it('contains no external-system identifiers or URLs', () => {
    const markup = render({ kind: 'loaded', member: memberFixture, ...metadata }).root.innerHTML.toLowerCase();
    expect(markup).not.toMatch(/northstar|heritage|customerkey|productkey|soap|https?:\/\//);
  });
  it('renders an intentional account-empty state without implying a missing member', () => {
    const { root } = render({ kind: 'empty', member: { ...memberFixture, accounts: [] }, ...metadata });
    expect(root.textContent).toContain('don’t have any accounts available');
    expect(root.textContent).not.toContain('could not be found');
  });
  it.each([[404, 'Member information could not be found.'], [503, 'temporarily unavailable']])('renders safe status %s wording', (status, wording) => {
    const { root } = render({ kind: 'error', error: new HarborApiError(status as number, 'diagnostic_code', 'stack trace'), ...metadata });
    expect(root.textContent).toContain(wording);
    expect(root.textContent).not.toMatch(/diagnostic_code|stack trace/);
    expect(root.querySelector('[role="alert"]')).not.toBeNull();
    expect(root.querySelector('button')?.textContent).toBe('Retry');
  });
  it('retry is an actual button wired to the page', () => {
    const { root, page } = render({ kind: 'error', error: new Error('secret exception') , ...metadata });
    root.querySelector('button')?.click();
    expect(page.retry).toHaveBeenCalledOnce();
    expect(root.textContent).not.toContain('secret exception');
  });
  it('provides a labelled selector and announces loading without old financial content', () => {
    const { root, page } = render({ kind: 'loading', requestId: 'request-0002', requestedMemberId: 'member-0002' });
    const selector = root.querySelector<HTMLSelectElement>('#member-selector')!;
    expect(root.querySelector(`label[for="${selector.id}"]`)).not.toBeNull();
    expect(root.querySelector('[role="status"]')?.textContent).toContain('Loading member information');
    expect(root.textContent).not.toContain('$2,450.75');
    selector.value = 'member-0002';
    selector.dispatchEvent(new Event('change'));
    expect(page.selectedMemberId).toBe('member-0002');
  });
});
