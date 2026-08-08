<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\Member;

final readonly class MemberFinancialOverview
{
    /** @param list<AccountFinancialOverview> $accounts */
    public function __construct(public Member $member, public array $accounts)
    {
        if (count($accounts) !== count($member->accounts)) throw new \InvalidArgumentException('A complete overview requires details for every member account.');
        foreach ($accounts as $account) if (!$account instanceof AccountFinancialOverview) throw new \InvalidArgumentException('Overview accounts must be AccountFinancialOverview values.');
    }
}
