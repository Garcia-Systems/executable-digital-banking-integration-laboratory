import { describe, expect, it, vi } from 'vitest';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { HarborApiClient } from '../src/api';

describe('Chapter 17 sensitive data handling', () => {
  it('does not add durable browser storage, cookies, or console logging', () => {
    const sources=['api.ts','contracts.ts','main.ts','render.ts','state.ts','transfer.ts']
      .map(file=>readFileSync(resolve(process.cwd(),'src',file),'utf8'))
      .join('\n');
    for(const forbidden of ['localStorage','sessionStorage','indexedDB','document.cookie','console.log','console.debug']) expect(sources).not.toContain(forbidden);
  });

  it('sends amount and memo in a POST body rather than the URL', async () => {
    const request=vi.fn<typeof fetch>(async()=>new Response(JSON.stringify({previewId:'preview-0001',memberId:'member-0001',sourceAccount:{accountId:'account-0001',displayName:'Checking'},destinationAccount:{accountId:'account-0002',displayName:'Savings'},amount:{currency:'USD',minorUnits:50000,formatted:'$500.00'},sourceAvailableBalance:{currency:'USD',minorUnits:245075,formatted:'$2,450.75'},projectedAvailableBalance:{currency:'USD',minorUnits:195075,formatted:'$1,950.75'},memo:'Private memo'}),{status:200,headers:{'Content-Type':'application/json'}}));
    await new HarborApiClient('http://harbor.invalid',request as typeof fetch).previewTransfer('member-0001',{sourceAccountId:'account-0001',destinationAccountId:'account-0002',amount:{currency:'USD',minorUnits:50000},memo:'Private memo'});
    const call=request.mock.calls[0];
    expect(call).toBeDefined();
    if (!call) throw new Error('Expected one transfer-preview request.');
    const [url,options]=call;expect(String(url)).not.toContain('50000');expect(String(url)).not.toContain('Private');expect(options?.method).toBe('POST');expect(String(options?.body)).toContain('Private memo');
  });
});
