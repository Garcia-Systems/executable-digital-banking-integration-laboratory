<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar;

use Harbor\DigitalBankingLab\Integration\UnknownVendorIdentity;
use Harbor\DigitalBankingLab\Integration\Northstar\Model\{NorthstarCustomer, NorthstarCustomerKey, NorthstarCustomerStatus, NorthstarProduct, NorthstarProductClass, NorthstarProductKey, NorthstarProductState};

final readonly class DeterministicNorthstarClient implements NorthstarClient
{
    public function __construct(private string $scenario = 'normal') {}

    public function findCustomer(NorthstarCustomerKey $key): NorthstarCustomer
    {
        if ($key->value !== 'NS-CUST-4417') throw new UnknownVendorIdentity("Unknown Northstar customer: {$key->value}");
        $secondClass = $this->scenario === 'northstar-unsupported-product' ? 'MMA' : NorthstarProductClass::SAV;
        return new NorthstarCustomer($key, NorthstarCustomerStatus::ENABLED, 'Avery Morgan', [
            new NorthstarProduct(new NorthstarProductKey('NS-PROD-9001'), new NorthstarProductClass(NorthstarProductClass::DDA), 'Everyday Checking', 245075, NorthstarProductState::ACTIVE),
            new NorthstarProduct(new NorthstarProductKey('NS-PROD-9002'), new NorthstarProductClass($secondClass), 'Primary Savings', 812000, NorthstarProductState::ACTIVE),
        ]);
    }
}
