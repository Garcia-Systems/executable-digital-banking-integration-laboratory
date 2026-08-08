<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain\Member;

enum AccountStatus: string
{
    case OPEN = 'OPEN';
    case RESTRICTED = 'RESTRICTED';
    case CLOSED = 'CLOSED';
}
