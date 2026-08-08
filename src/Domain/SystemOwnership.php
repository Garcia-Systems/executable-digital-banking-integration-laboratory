<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

enum SystemOwnership: string
{
    case HARBOR = 'HARBOR';
    case VENDOR = 'VENDOR';
    case THIRD_PARTY = 'THIRD_PARTY';
}
