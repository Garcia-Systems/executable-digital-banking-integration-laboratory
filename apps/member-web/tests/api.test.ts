import { describe, expect, it, vi } from 'vitest';
import { HarborApiClient, HarborApiError } from '../src/api';
import { ContractError } from '../src/contracts';
import { memberFixture } from './fixture';

const response = (body: unknown, status = 200) => new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });

describe('HarborApiClient', () => {
  it('uses GET and constructs the encoded Harbor URL', async () => {
    const fetcher = vi.fn<typeof fetch>().mockResolvedValue(response(memberFixture));
    await new HarborApiClient('http://api.test/', fetcher).getMember('member/one');
    expect(fetcher).toHaveBeenCalledWith('http://api.test/api/members/member%2Fone', expect.objectContaining({ method: 'GET' }));
  });
  it('returns parsed member data', async () => {
    const client = new HarborApiClient('http://api.test', vi.fn<typeof fetch>().mockResolvedValue(response(memberFixture)));
    await expect(client.getMember('member-0001')).resolves.toEqual(memberFixture);
  });
  it('turns 404 into a typed API error', async () => {
    const client = new HarborApiClient('', vi.fn<typeof fetch>().mockResolvedValue(response({ code: 'member_not_found', message: 'Not found' }, 404)));
    await expect(client.getMember('missing')).rejects.toMatchObject({ status: 404, code: 'member_not_found' });
  });
  it.each([502, 503, 504])('preserves safe Harbor failure status %s', async status => {
    const client = new HarborApiClient('', vi.fn<typeof fetch>().mockResolvedValue(response({ code: 'temporarily_unavailable', details: 'private diagnostic' }, status)));
    const error = await client.getMember('member-0001').catch(value => value) as HarborApiError;
    expect(error).toBeInstanceOf(HarborApiError);
    expect(error).toMatchObject({ status, code: 'temporarily_unavailable' });
    expect(JSON.stringify(error)).not.toContain('private diagnostic');
  });
  it('fails explicitly for malformed successful JSON', async () => {
    const malformed = new Response('{not-json', { status: 200 });
    await expect(new HarborApiClient('', vi.fn<typeof fetch>().mockResolvedValue(malformed)).getMember('member-0001')).rejects.toBeInstanceOf(ContractError);
  });
});
