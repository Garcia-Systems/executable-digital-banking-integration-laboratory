<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\{AccountBalanceDetails, AccountId};

interface CoreBankingGateway
{
    public function accountBalanceDetails(AccountId $accountId): AccountBalanceDetails;
}
