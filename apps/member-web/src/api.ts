import { ContractError, parseMemberSummary, type MemberSummaryDto } from './contracts';

export class HarborApiError extends Error {
  constructor(public readonly status: number, public readonly code?: string, public readonly safeMessage?: string) {
    super(`Harbor API request failed with status ${status}.`);
    this.name = 'HarborApiError';
  }
}

type ErrorBody = { code?: unknown; message?: unknown };

export interface MemberApi {
  getMember(memberId: string): Promise<MemberSummaryDto>;
}

export class HarborApiClient implements MemberApi {
  constructor(private readonly baseUrl: string, private readonly request: typeof fetch = fetch) {}

  async getMember(memberId: string): Promise<MemberSummaryDto> {
    const url = `${this.baseUrl.replace(/\/$/, '')}/api/members/${encodeURIComponent(memberId)}`;
    const response = await this.request(url, { method: 'GET', headers: { Accept: 'application/json' } });
    let body: unknown;
    try {
      body = await response.json();
    } catch {
      throw response.ok ? new ContractError('Harbor returned malformed JSON.') : new HarborApiError(response.status);
    }
    if (!response.ok) {
      const error = body as ErrorBody;
      throw new HarborApiError(
        response.status,
        typeof error?.code === 'string' ? error.code : undefined,
        typeof error?.message === 'string' ? error.message : undefined
      );
    }
    return parseMemberSummary(body);
  }
}
