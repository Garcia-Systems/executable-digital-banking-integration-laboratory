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

describe('member rendering', () => {
  it('renders the complete Harbor member experience and exactly one h1', () => {
    const { root } = render({ kind: 'loaded', member: memberFixture });
    for (const value of ['Avery Morgan', 'Membership status:', 'Active', 'Everyday Checking', 'Primary Savings', '$2,450.75', '$8,120.00']) expect(root.textContent).toContain(value);
    expect(root.querySelectorAll('h1')).toHaveLength(1);
    expect(root.querySelectorAll('article')).toHaveLength(2);
    expect(root.innerHTML).toContain('account-0001');
  });
  it('contains no external-system identifiers or URLs', () => {
    const markup = render({ kind: 'loaded', member: memberFixture }).root.innerHTML.toLowerCase();
    expect(markup).not.toMatch(/northstar|heritage|customerkey|productkey|soap|https?:\/\//);
  });
  it('renders an intentional account-empty state without implying a missing member', () => {
    const { root } = render({ kind: 'empty', member: { ...memberFixture, accounts: [] } });
    expect(root.textContent).toContain('don’t have any accounts available');
    expect(root.textContent).not.toContain('could not be found');
  });
  it.each([[404, 'Member information could not be found.'], [503, 'temporarily unavailable']])('renders safe status %s wording', (status, wording) => {
    const { root } = render({ kind: 'error', error: new HarborApiError(status as number, 'diagnostic_code', 'stack trace') });
    expect(root.textContent).toContain(wording);
    expect(root.textContent).not.toMatch(/diagnostic_code|stack trace/);
    expect(root.querySelector('[role="alert"]')).not.toBeNull();
    expect(root.querySelector('button')?.textContent).toBe('Retry');
  });
  it('retry is an actual button wired to the page', () => {
    const { root, page } = render({ kind: 'error', error: new Error() });
    root.querySelector('button')?.click();
    expect(page.retry).toHaveBeenCalledOnce();
  });
});
