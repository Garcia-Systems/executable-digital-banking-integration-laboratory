import {describe,expect,it,vi} from 'vitest';
import {HarborApiClient,HarborApiError} from '../src/api';
import {parseMemberVerification} from '../src/contracts';
import {TransferForm} from '../src/transfer';

describe('Harbor verification experience',()=>{
 it('parses only Harbor-owned statuses',()=>{
  expect(parseMemberVerification({memberId:'member-0001',status:'verified'})).toEqual({memberId:'member-0001',status:'verified'});
  expect(()=>parseMemberVerification({memberId:'member-0001',status:'unknown'})).toThrow();
 });
 it('calls the Harbor verification resource with cancellation',async()=>{
  const request=vi.fn().mockResolvedValue(new Response(JSON.stringify({memberId:'member-0001',status:'review_required'}),{status:200}));
  const controller=new AbortController();const result=await new HarborApiClient('http://harbor.test',request).getVerification('member-0001',controller.signal);
  expect(result.status).toBe('review_required');expect(request.mock.calls[0][0]).toContain('/api/members/member-0001/verification');expect(request.mock.calls[0][1].signal).toBe(controller.signal);
 });
 it.each([
  ['verification_review_required','Your verification requires review before this request can continue.'],
  ['member_verification_required','Verification is required before this request can continue.'],
 ])('renders workflow eligibility outside field errors',async(code,message)=>{
  const form=new TransferForm({previewTransfer:vi.fn().mockRejectedValue(new HarborApiError(409,code))},()=> 'member-0001',()=>undefined);
  form.state.fields={sourceAccountId:'account-0001',destinationAccountId:'account-0002',amount:'1.00',memo:''};await form.submit();
  expect(form.state.generalError).toBe(message);expect(form.state.errors).toEqual({});expect(form.state.generalError).not.toMatch(/CLEARVERIFY|MANUAL_REVIEW|PASS|FAIL/);
 });
});
