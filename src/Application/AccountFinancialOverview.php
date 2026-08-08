<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\{Account, Money};

/** Harbor-owned application result for one account; it composes rather than copies domain values. */
final readonly class AccountFinancialOverview
{
    public function __construct(public Account $account, public Money $ledgerBalance, public Money $availableBalance) {}
}
