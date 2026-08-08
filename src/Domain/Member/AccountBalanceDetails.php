<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Domain\Member;

/** A focused Harbor capability; it deliberately does not expand Account. */
final readonly class AccountBalanceDetails
{
    public function __construct(
        public AccountId $accountId,
        public Money $ledgerBalance,
        public Money $availableBalance,
        public AccountStatus $status,
    ) {}
}
