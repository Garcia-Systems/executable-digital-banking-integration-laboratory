<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\{Member, Money};

final class MemberSummaryRenderer
{
    public function render(Member $member): string
    {
        $lines = [
            "Member: {$member->name}",
            "Member ID: {$member->id->value}",
            "Status: {$member->status->value}",
            '',
            'Accounts:',
            '',
        ];
        $total = Money::usd(0);
        foreach ($member->accounts as $account) {
            $lines[] = "- {$account->displayName}";
            $lines[] = "  ID: {$account->id->value}";
            $lines[] = "  Type: {$account->type->value}";
            $lines[] = "  Balance: {$account->balance->format()}";
            $lines[] = "  Status: {$account->status->value}";
            $total = $total->add($account->balance);
        }
        $lines[] = '';
        $lines[] = "Total displayed balance: {$total->format()}";

        return implode("\n", $lines) . "\n";
    }
}
