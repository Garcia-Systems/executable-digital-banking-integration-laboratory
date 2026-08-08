import type { HarborApiError, MemberApi } from './api';
import type { MemberSummaryDto } from './contracts';

export type MemberPageState =
  | { kind: 'loading' }
  | { kind: 'loaded'; member: MemberSummaryDto }
  | { kind: 'empty'; member: MemberSummaryDto }
  | { kind: 'error'; error: unknown };

export class MemberPage {
  state: MemberPageState = { kind: 'loading' };
  private listener: (state: MemberPageState) => void = () => undefined;

  constructor(private readonly api: MemberApi, private readonly memberId: string) {}

  subscribe(listener: (state: MemberPageState) => void): void {
    this.listener = listener;
    listener(this.state);
  }

  async load(): Promise<void> {
    this.setState({ kind: 'loading' });
    try {
      const member = await this.api.getMember(this.memberId);
      this.setState(member.accounts.length === 0 ? { kind: 'empty', member } : { kind: 'loaded', member });
    } catch (error: unknown) {
      this.setState({ kind: 'error', error: error as HarborApiError });
    }
  }

  retry(): Promise<void> { return this.load(); }

  private setState(state: MemberPageState): void {
    this.state = state;
    this.listener(state);
  }
}
