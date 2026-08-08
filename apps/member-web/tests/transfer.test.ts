import { describe,expect,it,vi } from 'vitest';
import { parseUsdAmountToMinorUnits,validateTransferForm,TransferForm } from '../src/transfer';
import type { TransferPreviewDto } from '../src/contracts';

const preview:TransferPreviewDto={previewId:'preview-0001',memberId:'member-0001',sourceAccount:{accountId:'account-0001',displayName:'Everyday Checking'},destinationAccount:{accountId:'account-0002',displayName:'Primary Savings'},amount:{currency:'USD',minorUnits:50000,formatted:'$500.00'},sourceAvailableBalance:{currency:'USD',minorUnits:238575,formatted:'$2,385.75'},projectedAvailableBalance:{currency:'USD',minorUnits:188575,formatted:'$1,885.75'},memo:'Move to savings'};
describe('USD decimal parser',()=>{
 it.each([['500',50000],['500.00',50000],['0.01',1],['12.3',1230]])('%s', (value,expected)=>expect(parseUsdAmountToMinorUnits(value)).toBe(expected));
 it.each(['12.345','abc','-1.00','','$1.00','1,000'])('rejects %s',value=>expect(parseUsdAmountToMinorUnits(value)).toBeNull());
});
describe('transfer form',()=>{
 it('validates all local rules',()=>{expect(validateTransferForm({sourceAccountId:'',destinationAccountId:'',amount:'',memo:'x'.repeat(141)})).toMatchObject({sourceAccountId:expect.anything(),destinationAccountId:expect.anything(),amount:expect.anything(),memo:expect.anything()}); expect(validateTransferForm({sourceAccountId:'a',destinationAccountId:'a',amount:'0',memo:''}).destinationAccountId).toBeDefined(); expect(validateTransferForm({sourceAccountId:'a',destinationAccountId:'b',amount:'1.00',memo:''})).toEqual({});});
 it('does not call API for invalid input',async()=>{const api={previewTransfer:vi.fn()};const form=new TransferForm(api,()=> 'member-0001',()=>{});await form.submit();expect(api.previewTransfer).not.toHaveBeenCalled();});
 it('submits integer money, succeeds, and invalidates stale preview on edit',async()=>{let resolve!:(p:TransferPreviewDto)=>void;const api={previewTransfer:vi.fn(()=>new Promise<TransferPreviewDto>(r=>resolve=r))};const form=new TransferForm(api,()=> 'member-0001',()=>{});form.update('sourceAccountId','account-0001');form.update('destinationAccountId','account-0002');form.update('amount','500.00');form.update('memo','Move to savings');const pending=form.submit();expect(form.state.kind).toBe('submitting');expect(api.previewTransfer).toHaveBeenCalledWith('member-0001',expect.objectContaining({amount:{currency:'USD',minorUnits:50000}}));resolve(preview);await pending;expect(form.state.kind).toBe('succeeded');expect(form.state.preview?.projectedAvailableBalance.formatted).toBe('$1,885.75');form.update('memo','changed');expect(form.state.preview).toBeUndefined();});
});
