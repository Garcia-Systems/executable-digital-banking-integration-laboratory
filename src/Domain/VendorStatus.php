<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

enum VendorStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case SLOW = 'SLOW';
    case UNAVAILABLE = 'UNAVAILABLE';
}
