import { ValidationError, type TransferApi } from './api';
import type { TransferPreviewDto } from './contracts';

export interface TransferFormModel { sourceAccountId:string; destinationAccountId:string; amount:string; memo:string; }
export type FieldErrors=Partial<Record<keyof TransferFormModel|string,string[]>>;
export type TransferFormState={kind:'editing'|'submitting'|'succeeded'|'failed';fields:TransferFormModel;errors:FieldErrors;preview?:TransferPreviewDto;generalError?:string};

export function parseUsdAmountToMinorUnits(value:string):number|null {
  const match=/^(0|[1-9]\d*)(?:\.(\d{1,2}))?$/.exec(value);
  if (!match) return null;
  const cents=(match[2]??'').padEnd(2,'0');
  const result=Number(`${match[1]}${cents}`);
  return Number.isSafeInteger(result)?result:null;
}
export function validateTransferForm(fields:TransferFormModel):FieldErrors {
  const errors:FieldErrors={};
  if (!fields.sourceAccountId) errors.sourceAccountId=['Choose a source account.'];
  if (!fields.destinationAccountId) errors.destinationAccountId=['Choose a destination account.'];
  if (fields.sourceAccountId && fields.sourceAccountId===fields.destinationAccountId) errors.destinationAccountId=['Source and destination accounts must be different.'];
  if (!fields.amount) errors.amount=['Enter an amount.']; else { const amount=parseUsdAmountToMinorUnits(fields.amount); if (amount===null) errors.amount=['Enter a USD amount with no more than two decimal places.']; else if (amount===0) errors.amount=['Amount must be greater than zero.']; }
  if (fields.memo.length>140) errors.memo=['Memo must be 140 characters or fewer.'];
  return errors;
}
export class TransferForm {
  state:TransferFormState={kind:'editing',fields:{sourceAccountId:'',destinationAccountId:'',amount:'',memo:''},errors:{}};
  constructor(private api:Partial<TransferApi>,private memberId:()=>string,private changed:()=>void){}
  update(field:keyof TransferFormModel,value:string):void { this.state={kind:'editing',fields:{...this.state.fields,[field]:value},errors:{...this.state.errors,[field]:undefined},preview:undefined}; this.changed(); }
  reset():void { this.state={kind:'editing',fields:{sourceAccountId:'',destinationAccountId:'',amount:'',memo:''},errors:{}}; this.changed(); }
  async submit():Promise<void>{
    if(this.state.kind==='submitting')return; const errors=validateTransferForm(this.state.fields);
    if(Object.keys(errors).length){this.state={...this.state,kind:'editing',errors,preview:undefined};this.changed();return;}
    const fields=this.state.fields; const minorUnits=parseUsdAmountToMinorUnits(fields.amount)!;
    this.state={kind:'submitting',fields,errors:{}};this.changed();
    try { if(!this.api.previewTransfer)throw new Error('Transfer API unavailable'); const preview=await this.api.previewTransfer(this.memberId(),{sourceAccountId:fields.sourceAccountId,destinationAccountId:fields.destinationAccountId,amount:{currency:'USD',minorUnits},memo:fields.memo||null}); this.state={kind:'succeeded',fields,errors:{},preview}; }
    catch(error){ if(error instanceof ValidationError)this.state={kind:'failed',fields,errors:error.fields}; else this.state={kind:'failed',fields,errors:{},generalError:'Transfer preview is temporarily unavailable.'}; }
    this.changed();
  }
}
