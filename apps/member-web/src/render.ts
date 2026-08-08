import { HarborApiError } from './api';
import { displayLabel } from './presentation';
import type { MemberPage, MemberPageState } from './state';

const escapeHtml = (value: string): string => value.replace(/[&<>'"]/g, character => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
})[character] as string);

export function renderMemberPage(root: HTMLElement, state: MemberPageState, page: MemberPage): void {
  if (state.kind === 'loading') {
    root.innerHTML = '<section class="state-panel" aria-live="polite"><h1 id="page-title">Your accounts</h1><p role="status">Loading your accounts…</p></section>';
    return;
  }
  if (state.kind === 'error') {
    const status = state.error instanceof HarborApiError ? state.error.status : 0;
    const message = status === 404
      ? 'Member information could not be found.'
      : [502, 503, 504].includes(status)
        ? 'Your account information is temporarily unavailable.'
        : 'We couldn’t load your accounts right now.';
    root.innerHTML = `<section class="state-panel error" role="alert" aria-labelledby="page-title"><h1 id="page-title">We’re sorry</h1><p>${message}</p><button type="button" id="retry">Retry</button></section>`;
    root.querySelector<HTMLButtonElement>('#retry')?.addEventListener('click', () => void page.retry());
    return;
  }
  const member = state.member;
  const introduction = `<section class="member-heading" aria-labelledby="page-title"><p class="eyebrow">Member overview</p><h1 id="page-title">Welcome, ${escapeHtml(member.name)}</h1><p>Membership status: <strong>${displayLabel(member.status)}</strong></p></section>`;
  if (state.kind === 'empty') {
    root.innerHTML = `${introduction}<section class="state-panel" aria-labelledby="accounts-title"><h2 id="accounts-title">Your accounts</h2><p>You don’t have any accounts available in digital banking.</p></section>`;
    return;
  }
  const cards = member.accounts.map(account => `<article class="account-card" data-account-id="${escapeHtml(account.accountId)}"><h3>${escapeHtml(account.displayName)}</h3><p class="account-type">${displayLabel(account.type)}</p><p class="balance"><span class="sr-only">Digital banking balance: </span>${escapeHtml(account.balance.formatted)}</p><p>Account status: <strong>${displayLabel(account.status)}</strong></p></article>`).join('');
  root.innerHTML = `${introduction}<section aria-labelledby="accounts-title"><h2 id="accounts-title">Your accounts</h2><div class="account-grid">${cards}</div></section>`;
}
