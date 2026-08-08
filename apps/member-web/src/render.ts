import { HarborApiError } from './api';
import { displayLabel } from './presentation';
import type { MemberPage, MemberPageState } from './state';
import type { TransferFormModel } from './transfer';

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
      content = `${introduction}${verification(page)}<section class="state-panel" aria-labelledby="accounts-title"><h2 id="accounts-title">Your accounts</h2><p>You don’t have any accounts available in digital banking.</p></section>`;
    } else {
      const cards = member.accounts.map(account => `<article class="account-card" data-account-id="${escapeHtml(account.accountId)}"><h3>${escapeHtml(account.displayName)}</h3><p class="account-type">${displayLabel(account.type)}</p><p class="balance"><span class="sr-only">Digital banking balance: </span>${escapeHtml(account.balance.formatted)}</p><p>Account status: <strong>${displayLabel(account.status)}</strong></p></article>`).join('');
      content = `${introduction}${verification(page)}<section aria-labelledby="accounts-title"><h2 id="accounts-title">Your accounts</h2><div class="account-grid">${cards}</div></section>${transferForm(page, member.accounts)}`;
    }
  }

  root.innerHTML = `<nav class="member-nav" aria-label="Member navigation"><a href="#accounts-title" data-destination="accounts">Accounts</a><a href="#transfer-title" data-destination="transfer">Transfer Preview</a><a href="/help/transfer-preview.html" data-destination="help">Help</a></nav>${selector(page.selectedMemberId)}${content}`;
  root.querySelectorAll<HTMLAnchorElement>('[data-destination]').forEach(link=>link.addEventListener('click',()=>page.analytics.record('navigation_selected',{destination:link.dataset.destination as 'accounts'|'transfer'|'help'})));
  root.querySelector<HTMLSelectElement>('#member-selector')?.addEventListener('change', event => {
    void page.selectMember((event.currentTarget as HTMLSelectElement).value);
  });
  root.querySelector<HTMLButtonElement>('#retry')?.addEventListener('click', () => void page.retry());
  root.querySelector<HTMLFormElement>('#transfer-form')?.addEventListener('submit', event => { event.preventDefault(); void page.transfer.submit(); });
  for (const field of ['sourceAccountId','destinationAccountId','amount','memo'] as const) root.querySelector<HTMLInputElement|HTMLSelectElement>(`[name="${field}"]`)?.addEventListener('input',event=>page.transfer.update(field,(event.currentTarget as HTMLInputElement|HTMLSelectElement).value));
}

function verification(page:MemberPage):string{
  const state=page.verification;if(state.kind==='loading')return '<section aria-labelledby="verification-title"><h2 id="verification-title">Verification</h2><p>Loading verification status…</p></section>';
  if(state.kind==='error')return '<section aria-labelledby="verification-title"><h2 id="verification-title">Verification</h2><p>Verification status is temporarily unavailable.</p></section>';
  if(state.kind!=='loaded'||!state.result)return '';
  const label={verified:'Verified',review_required:'Verification review required',not_verified:'Verification required'}[state.result.status];
  return `<section aria-labelledby="verification-title"><h2 id="verification-title">Verification</h2><p>${label}</p></section>`;
}

function transferForm(page:MemberPage,accounts:{accountId:string;displayName:string}[]):string {
  const state=page.transfer.state, values=state.fields;
  const options=(selected:string)=>`<option value="">Choose an account</option>${accounts.map(a=>`<option value="${escapeHtml(a.accountId)}"${a.accountId===selected?' selected':''}>${escapeHtml(a.displayName)}</option>`).join('')}`;
  const messages=(name:keyof TransferFormModel):string[] => state.errors[name]??(name==='amount'?state.errors['amount.minorUnits']:undefined)??[];
  const field=(name:keyof TransferFormModel)=>messages(name).length?`<p id="${name}-error" class="field-error">${messages(name).map(escapeHtml).join(' ')}</p>`:'';
  const described=(name:keyof TransferFormModel)=>messages(name).length?` aria-describedby="${name}-error" aria-invalid="true"`:'';
  const preview=state.preview?`<section class="preview-panel" role="status" aria-labelledby="preview-title"><h3 id="preview-title">Transfer Preview</h3><dl><dt>From</dt><dd>${escapeHtml(state.preview.sourceAccount.displayName)}</dd><dt>To</dt><dd>${escapeHtml(state.preview.destinationAccount.displayName)}</dd><dt>Amount</dt><dd>${escapeHtml(state.preview.amount.formatted)}</dd><dt>Current available balance</dt><dd>${escapeHtml(state.preview.sourceAvailableBalance.formatted)}</dd><dt>Projected available balance</dt><dd>${escapeHtml(state.preview.projectedAvailableBalance.formatted)}</dd><dt>Memo</dt><dd>${escapeHtml(state.preview.memo??'None')}</dd></dl><p class="no-mutation"><strong>No funds have been moved.</strong></p></section>`:'';
  return `<section class="transfer" aria-labelledby="transfer-title"><h2 id="transfer-title">Transfer Preview</h2><p>Review how Harbor would interpret an instruction. This does not move money.</p><form id="transfer-form" novalidate><label for="sourceAccountId">From account</label><select id="sourceAccountId" name="sourceAccountId"${described('sourceAccountId')}>${options(values.sourceAccountId)}</select>${field('sourceAccountId')}<label for="destinationAccountId">To account</label><select id="destinationAccountId" name="destinationAccountId"${described('destinationAccountId')}>${options(values.destinationAccountId)}</select>${field('destinationAccountId')}<label for="amount">Amount (USD)</label><input id="amount" name="amount" inputmode="decimal" value="${escapeHtml(values.amount)}"${described('amount')}>${field('amount')}<label for="memo">Memo (optional)</label><textarea id="memo" name="memo" maxlength="140"${described('memo')}>${escapeHtml(values.memo)}</textarea>${field('memo')}<button type="submit"${state.kind==='submitting'?' disabled':''}>${state.kind==='submitting'?'Creating preview…':'Preview transfer'}</button>${state.generalError?`<p class="general-error" role="alert">${escapeHtml(state.generalError)}</p>`:''}</form>${preview}</section>`;
}
