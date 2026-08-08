import { RequestAbortedError, type MemberApi } from './api';
import { ContractError, type MemberSummaryDto } from './contracts';
import { TransferForm } from './transfer';

export type RequestId = `request-${string}`;

type RequestMetadata = { requestId: RequestId; requestedMemberId: string };
export type MemberPageState =
  | { kind: 'idle' }
  | ({ kind: 'loading' } & RequestMetadata)
  | ({ kind: 'loaded'; member: MemberSummaryDto } & RequestMetadata)
  | ({ kind: 'empty'; member: MemberSummaryDto } & RequestMetadata)
  | ({ kind: 'error'; error: unknown } & RequestMetadata);

export type RequestEvent =
  | ({ type: 'REQUEST_STARTED' } & RequestMetadata)
  | ({ type: 'REQUEST_SUCCEEDED'; member: MemberSummaryDto } & RequestMetadata)
  | ({ type: 'REQUEST_FAILED'; error: unknown } & RequestMetadata);

export function reduceMemberRequestState(state: MemberPageState, event: RequestEvent): MemberPageState {
  if (event.type === 'REQUEST_STARTED') return { kind: 'loading', requestId: event.requestId, requestedMemberId: event.requestedMemberId };
  if (state.kind === 'idle' || state.requestId !== event.requestId || state.requestedMemberId !== event.requestedMemberId) return state;
  if (event.type === 'REQUEST_FAILED') return { kind: 'error', requestId: event.requestId, requestedMemberId: event.requestedMemberId, error: event.error };
  return event.member.accounts.length === 0
    ? { kind: 'empty', requestId: event.requestId, requestedMemberId: event.requestedMemberId, member: event.member }
    : { kind: 'loaded', requestId: event.requestId, requestedMemberId: event.requestedMemberId, member: event.member };
}

export class RequestSequence {
  private value = 0;
  next(): RequestId { return `request-${String(++this.value).padStart(4, '0')}`; }
}

export class RequestTrace {
  readonly entries: string[] = [];
  record(entry: string): void { this.entries.push(entry); }
}

export class MemberPage {
  state: MemberPageState = { kind: 'idle' };
  selectedMemberId: string;
  private listener: (state: MemberPageState) => void = () => undefined;
  private active?: { requestId: RequestId; controller: AbortController };
  readonly transfer: TransferForm;

  constructor(
    private readonly api: MemberApi,
    memberId: string,
    private readonly sequence = new RequestSequence(),
    readonly trace = new RequestTrace()
  ) { this.selectedMemberId = memberId; this.transfer=new TransferForm(api as MemberApi & Partial<import('./api').TransferApi>,()=>this.selectedMemberId,()=>this.listener(this.state)); }

  subscribe(listener: (state: MemberPageState) => void): void {
    this.listener = listener;
    listener(this.state);
  }

  selectMember(memberId: string): Promise<void> {
    this.selectedMemberId = memberId;
    this.transfer.reset();
    return this.load(memberId);
  }

  async load(memberId = this.selectedMemberId): Promise<void> {
    if (this.active) {
      this.active.controller.abort();
      this.trace.record(`${this.active.requestId} ABORT`);
    }
    const requestId = this.sequence.next();
    const controller = new AbortController();
    this.active = { requestId, controller };
    this.trace.record(`${requestId} START ${memberId}`);
    this.transition({ type: 'REQUEST_STARTED', requestId, requestedMemberId: memberId });

    try {
      const member = await this.api.getMember(memberId, controller.signal);
      if (!this.isCurrent(requestId)) {
        this.trace.record(`${requestId} STALE_SUCCESS_IGNORED`);
        return;
      }
      if (member.memberId !== memberId) throw new ContractError('Member response did not match the request.');
      this.trace.record(`${requestId} SUCCESS`);
      this.transition({ type: 'REQUEST_SUCCEEDED', requestId, requestedMemberId: memberId, member });
    } catch (error: unknown) {
      if (!this.isCurrent(requestId)) {
        this.trace.record(`${requestId} STALE_FAILURE_IGNORED`);
        return;
      }
      if (error instanceof RequestAbortedError || controller.signal.aborted) {
        this.trace.record(`${requestId} ABORTED_IGNORED`);
        return;
      }
      this.trace.record(`${requestId} FAILURE`);
      this.transition({ type: 'REQUEST_FAILED', requestId, requestedMemberId: memberId, error });
    } finally {
      if (this.isCurrent(requestId)) this.active = undefined;
    }
  }

  retry(): Promise<void> { return this.load(this.selectedMemberId); }
  get activeRequestId(): RequestId | undefined { return this.active?.requestId; }

  private isCurrent(requestId: RequestId): boolean { return this.active?.requestId === requestId; }
  private transition(event: RequestEvent): void {
    this.state = reduceMemberRequestState(this.state, event);
    this.listener(this.state);
  }
}
