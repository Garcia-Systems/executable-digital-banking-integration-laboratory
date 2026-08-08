<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\{AccountBalanceDetails, AccountId};

/** A Harbor-owned account-balance capability; no vendor or transport types cross it. */
interface AccountBalanceGateway
{
    public function accountBalanceDetails(AccountId $accountId): AccountBalanceDetails;
}
