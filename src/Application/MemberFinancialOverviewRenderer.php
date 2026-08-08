<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

final readonly class MemberFinancialOverviewRenderer
{
    public function render(MemberFinancialOverview $overview): string
    {
        $member = $overview->member;
        $output = "Member Financial Overview\n\nMember: {$member->name}\nMember ID: {$member->id->value}\nStatus: {$member->status->value}\n\n";
        foreach ($overview->accounts as $item) {
            $account = $item->account;
            $output .= "Account: {$account->displayName}\nAccount ID: {$account->id->value}\nType: {$account->type->value}\n";
            $output .= "Digital banking balance: {$account->balance->format()}\nLedger balance: {$item->ledgerBalance->format()}\nAvailable balance: {$item->availableBalance->format()}\nStatus: {$account->status->value}\n\n";
        }
        return $output . 'Accounts: ' . count($overview->accounts) . "\n";
    }
}
