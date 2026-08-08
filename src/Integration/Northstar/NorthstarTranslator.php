<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar;

use Harbor\DigitalBankingLab\Domain\Member\{AccountStatus, AccountType, MembershipStatus};
use Harbor\DigitalBankingLab\Integration\VendorTranslationException;
use Harbor\DigitalBankingLab\Integration\Northstar\Model\{NorthstarCustomerStatus, NorthstarProductClass, NorthstarProductState};

final class NorthstarTranslator
{
    public function membershipStatus(NorthstarCustomerStatus $status): MembershipStatus
    { return match ($status) { NorthstarCustomerStatus::ENABLED => MembershipStatus::ACTIVE }; }

    public function accountType(NorthstarProductClass $class): AccountType
    {
        return match ($class->value) {
            NorthstarProductClass::DDA => AccountType::CHECKING,
            NorthstarProductClass::SAV => AccountType::SAVINGS,
            default => throw new VendorTranslationException("Unsupported Northstar productClass: {$class->value}"),
        };
    }

    public function accountStatus(NorthstarProductState $state): AccountStatus
    { return match ($state) { NorthstarProductState::ACTIVE => AccountStatus::OPEN }; }
}
