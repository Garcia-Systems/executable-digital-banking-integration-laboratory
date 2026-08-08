<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain\Member;

enum MembershipStatus: string
{
    case ACTIVE = 'ACTIVE';
    case RESTRICTED = 'RESTRICTED';
    case CLOSED = 'CLOSED';
}
