<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

enum IntegrationOperation: string
{
    case MEMBER_LOOKUP = 'member lookup';
    case ACCOUNT_BALANCE_LOOKUP = 'account balance lookup';
    case MEMBER_VERIFICATION = 'member verification';
}
