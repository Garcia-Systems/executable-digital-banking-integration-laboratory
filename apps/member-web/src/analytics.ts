export type AnalyticsName='page_view'|'member_summary_loaded'|'transfer_preview_started'|'transfer_preview_validation_failed'|'transfer_preview_succeeded'|'navigation_selected'|'help_opened';
export type SafeProperties={error_category?:'amount_format'|'same_account'|'insufficient_available_balance'|'verification_required';destination?:'accounts'|'transfer'|'help'};
export interface SafeAnalyticsEvent { readonly name:AnalyticsName;readonly properties:Readonly<SafeProperties>; }
export interface AnalyticsRecorder { record(name:AnalyticsName,properties?:SafeProperties):void; }
const allowed:Record<AnalyticsName,readonly string[]>={page_view:[],member_summary_loaded:[],transfer_preview_started:[],transfer_preview_validation_failed:['error_category'],transfer_preview_succeeded:[],navigation_selected:['destination'],help_opened:[]};
export class InMemoryAnalyticsRecorder implements AnalyticsRecorder {
  readonly events:SafeAnalyticsEvent[]=[];
  record(name:AnalyticsName,properties:SafeProperties={}):void { if(Object.keys(properties).some(key=>!allowed[name].includes(key)))throw new Error('Analytics property is not allow-listed.');this.events.push(Object.freeze({name,properties:Object.freeze({...properties})})); }
}
export const noAnalytics:AnalyticsRecorder={record:()=>undefined};
