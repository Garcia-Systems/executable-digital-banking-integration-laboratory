<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\{AccountBalanceDetails, AccountId};

final readonly class GetAccountBalanceDetails
{
    public function __construct(private CoreBankingGateway $coreBanking) {}
    public function execute(AccountId $accountId): AccountBalanceDetails
    {
        return $this->coreBanking->accountBalanceDetails($accountId);
    }
}
