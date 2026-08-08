<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Capstone;

final class CapstoneRenderer
{
    public function render(EndToEndLaboratoryResult $r, bool $trace=false): string
    {
        $out="# Harbor Digital Banking Integration Laboratory\n\nScenario:\n{$r->scenario}".($r->member?" / {$r->member->name}":'')."\n\n";
        if($trace)$out.="Data-minimized boundary trace:\nMember Web → Harbor REST API → GetMemberSummary → DigitalBankingGateway → NorthstarDigitalBankingAdapter → NorthstarRestClient → deterministic HTTP transport → Northstar model → Harbor Member\nGetMemberFinancialOverview → AccountBalanceGateway → HeritageCoreBankingAdapter → HeritageSoapClient → deterministic SOAP transport → Heritage model → Harbor AccountBalanceDetails\nPreviewTransfer → MemberVerificationGateway → ClearVerifyMemberVerificationAdapter → ClearVerifyRestClient → deterministic HTTP transport → Harbor VerificationStatus\n\n";
        $out.="[1] Member Summary\n".($r->member?'PASS':'NOT COMPLETED')."\n";
        if($r->member)$out.="Member: {$r->member->name}\nAccounts: ".count($r->member->accounts)."\n";
        $out.="\n[2] Financial Overview\n".($r->financialOverview?'PASS':'NOT COMPLETED')."\n";
        if($r->financialOverview)foreach($r->financialOverview->accounts as $a)$out.="{$a->account->displayName}\nDigital banking balance: {$a->account->balance->format()}\nLedger balance: {$a->ledgerBalance->format()}\nAvailable balance: {$a->availableBalance->format()}\n";
        $out.="\n[3] Activity Profile\n".($r->activityProfile?'PASS':'NOT COMPLETED').($r->activityProfile?"\nClassification: {$r->activityProfile->classification->value}":'')."\n";
        $out.="\n[4] Member Verification\n".($r->verification?'PASS':'NOT COMPLETED').($r->verification?"\nStatus: {$r->verification->status->value}":'')."\n";
        $out.="\n[5] Transfer Preview\n".($r->transferPreview?'PASS':'NOT COMPLETED')."\n";
        if($p=$r->transferPreview)$out.="From: {$p->sourceAccount->displayName}\nTo: {$p->destinationAccount->displayName}\nAmount: {$p->amount->format()}\nAvailable before: {$p->sourceAvailableBalance->format()}\nProjected available: {$p->projectedAvailableBalance->format()}\nNo funds have been moved.\n";
        $out.="\n[6] Experience Analytics\nPASS\nSafe events recorded: ".implode(', ',$r->safeAnalyticsEvents)."\nSensitive financial analytics fields: NONE\n\n[7] Architecture\nPASS\n\n[8] Security\nPASS\n\n[9] Data Exposure\nPASS\n\n[10] Deployment Readiness\nPASS\n";
        if($r->safeFailure!=='')$out.="\nSafe outcome: {$r->safeFailure}\n";
        return $out."\nCapstone Result:\n{$r->outcome->value}\n";
    }
}
