<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

enum SystemCategory: string
{
    case CHANNEL = 'CHANNEL';
    case APPLICATION = 'APPLICATION';
    case DATABASE = 'DATABASE';
    case VENDOR_PLATFORM = 'VENDOR_PLATFORM';
    case LEGACY_SYSTEM = 'LEGACY_SYSTEM';
    case FINTECH_SERVICE = 'FINTECH_SERVICE';
}
