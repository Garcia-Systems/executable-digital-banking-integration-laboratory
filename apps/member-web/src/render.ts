import { HarborApiError } from './api';
import { displayLabel } from './presentation';
import type { MemberPage, MemberPageState } from './state';

const escapeHtml = (value: string): string => value.replace(/[&<>'"]/g, character => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
})[character] as string);

const selector = (selectedMemberId: string): string => `<section class="member-controls" aria-labelledby="member-selector-title">
  <h1 id="page-title">Your accounts</h1>
  <h2 id="member-selector-title" class="control-title">Laboratory member</h2>
  <label for="member-selector">Choose a fictional member</label>
  <select id="member-selector">
    ${['member-0001', 'member-0002', 'member-0003'].map(id => `<option value="${id}"${id === selectedMemberId ? ' selected' : ''}>${id}</option>`).join('')}
  </select>
  <p class="teaching-note">This laboratory selector is not authentication.</p>
</section>`;

export function renderMemberPage(root: HTMLElement, state: MemberPageState, page: MemberPage): void {
  let content: string;
  if (state.kind === 'idle' || state.kind === 'loading') {
    content = '<section class="state-panel"><p role="status" aria-live="polite">Loading member information…</p></section>';
  } else if (state.kind === 'error') {
    const status = state.error instanceof HarborApiError ? state.error.status : 0;
    const message = status === 404
      ? 'Member information could not be found.'
      : [502, 503, 504].includes(status)
        ? 'Member information is temporarily unavailable.'
        : 'We couldn’t load member information right now.';
    content = `<section class="state-panel error" role="alert"><h2>We’re sorry</h2><p>${message}</p><button type="button" id="retry">Retry</button></section>`;
  } else {
    const member = state.member;
    const introduction = `<p class="request-status" role="status">Loaded member information.</p><section class="member-heading" aria-labelledby="member-name"><p class="eyebrow">Member overview</p><h2 id="member-name">Welcome, ${escapeHtml(member.name)}</h2><p>Membership status: <strong>${displayLabel(member.status)}</strong></p></section>`;
    if (state.kind === 'empty') {
      content = `${introduction}<section class="state-panel" aria-labelledby="accounts-title"><h2 id="accounts-title">Your accounts</h2><p>You don’t have any accounts available in digital banking.</p></section>`;
    } else {
      const cards = member.accounts.map(account => `<article class="account-card" data-account-id="${escapeHtml(account.accountId)}"><h3>${escapeHtml(account.displayName)}</h3><p class="account-type">${displayLabel(account.type)}</p><p class="balance"><span class="sr-only">Digital banking balance: </span>${escapeHtml(account.balance.formatted)}</p><p>Account status: <strong>${displayLabel(account.status)}</strong></p></article>`).join('');
      content = `${introduction}<section aria-labelledby="accounts-title"><h2 id="accounts-title">Your accounts</h2><div class="account-grid">${cards}</div></section>`;
    }
  }

  root.innerHTML = `${selector(page.selectedMemberId)}${content}`;
  root.querySelector<HTMLSelectElement>('#member-selector')?.addEventListener('change', event => {
    void page.selectMember((event.currentTarget as HTMLSelectElement).value);
  });
  root.querySelector<HTMLButtonElement>('#retry')?.addEventListener('click', () => void page.retry());
}
