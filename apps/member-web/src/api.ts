import { ContractError, parseMemberSummary, parseTransferPreview, type MemberSummaryDto, type TransferPreviewDto } from './contracts';

export class HarborApiError extends Error {
  readonly kind = 'http';

  constructor(public readonly status: number, public readonly code?: string, public readonly safeMessage?: string) {
    super(`Harbor API request failed with status ${status}.`);
    this.name = 'HarborApiError';
  }
}

export class ValidationError extends HarborApiError {
  constructor(public readonly fields: Record<string, string[]>) { super(422, 'validation_failed', 'The request contains invalid fields.'); this.name='ValidationError'; }
}

export class NetworkError extends Error {
  readonly kind = 'network';

  constructor() {
    super('The Harbor API could not be reached.');
    this.name = 'NetworkError';
  }
}

export class RequestAbortedError extends Error {
  readonly kind = 'aborted';

  constructor() {
    super('The request was aborted.');
    this.name = 'RequestAbortedError';
  }
}

type ErrorBody = { code?: unknown; message?: unknown };

export interface MemberApi {
  getMember(memberId: string, signal?: AbortSignal): Promise<MemberSummaryDto>;
}
export interface TransferApi { previewTransfer(memberId:string, request:TransferPreviewRequest):Promise<TransferPreviewDto>; }
export interface TransferPreviewRequest { sourceAccountId:string; destinationAccountId:string; amount:{currency:'USD';minorUnits:number}; memo:string|null; }

const isAbortFailure = (error: unknown, signal?: AbortSignal): boolean =>
  signal?.aborted === true || (error instanceof DOMException && error.name === 'AbortError') ||
  (typeof error === 'object' && error !== null && 'name' in error && error.name === 'AbortError');

export class HarborApiClient implements MemberApi {
  constructor(private readonly baseUrl: string, private readonly request: typeof fetch = fetch) {}

  async getMember(memberId: string, signal?: AbortSignal): Promise<MemberSummaryDto> {
    const url = `${this.baseUrl.replace(/\/$/, '')}/api/members/${encodeURIComponent(memberId)}`;
    let response: Response;
    try {
      response = await this.request(url, { method: 'GET', headers: { Accept: 'application/json' }, signal });
    } catch (error: unknown) {
      if (isAbortFailure(error, signal)) throw new RequestAbortedError();
      throw new NetworkError();
    }

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

  async previewTransfer(memberId:string, requestBody:TransferPreviewRequest):Promise<TransferPreviewDto> {
    const url=`${this.baseUrl.replace(/\/$/, '')}/api/members/${encodeURIComponent(memberId)}/transfer-preview`;
    let response:Response;
    try { response=await this.request(url,{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json'},body:JSON.stringify(requestBody)}); }
    catch { throw new NetworkError(); }
    let body:unknown; try { body=await response.json(); } catch { throw response.ok ? new ContractError('Harbor returned malformed JSON.') : new HarborApiError(response.status); }
    if (!response.ok) {
      const wrapper=body as {error?:{code?:unknown;message?:unknown;fields?:unknown}};
      if (response.status===422 && wrapper.error?.code==='validation_failed' && typeof wrapper.error.fields==='object' && wrapper.error.fields!==null) throw new ValidationError(wrapper.error.fields as Record<string,string[]>);
      throw new HarborApiError(response.status,typeof wrapper.error?.code==='string'?wrapper.error.code:undefined,typeof wrapper.error?.message==='string'?wrapper.error.message:undefined);
    }
    return parseTransferPreview(body);
  }
}
