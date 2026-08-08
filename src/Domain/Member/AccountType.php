<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain\Member;

enum AccountType: string
{
    case CHECKING = 'CHECKING';
    case SAVINGS = 'SAVINGS';
}
