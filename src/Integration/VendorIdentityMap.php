<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Integration;

use Harbor\DigitalBankingLab\Domain\Member\{AccountId, MemberId};
use Harbor\DigitalBankingLab\Integration\Northstar\Model\{NorthstarCustomerKey, NorthstarProductKey};

final readonly class VendorIdentityMap
{
    /** @param array<string, NorthstarCustomerKey> $customers @param array<string, NorthstarProductKey> $products */
    public function __construct(private array $customers, private array $products)
    {
    }

    public static function laboratory(): self
    {
        return new self(
            ['member-0001' => new NorthstarCustomerKey('NS-CUST-4417')],
            [
                'account-0001' => new NorthstarProductKey('NS-PROD-9001'),
                'account-0002' => new NorthstarProductKey('NS-PROD-9002'),
            ],
        );
    }

    public function northstarCustomerFor(MemberId $id): NorthstarCustomerKey
    {
        return $this->customers[$id->value] ?? throw new UnknownVendorIdentity("No Northstar customer mapping for Harbor member: {$id->value}");
    }

    public function northstarProductFor(AccountId $id): NorthstarProductKey
    {
        return $this->products[$id->value] ?? throw new UnknownVendorIdentity("No Northstar product mapping for Harbor account: {$id->value}");
    }

    public function harborAccountFor(NorthstarProductKey $key): AccountId
    {
        foreach ($this->products as $harborId => $northstarKey) {
            if ($northstarKey->value === $key->value) {
                return new AccountId($harborId);
            }
        }
        throw new UnknownVendorIdentity("No Harbor account mapping for Northstar product: {$key->value}");
    }

    /** @return array<AccountId, NorthstarProductKey> */
    public function accounts(): array
    {
        $mapped = [];
        foreach ($this->products as $id => $key) {
            $mapped[] = [new AccountId($id), $key];
        }
        return $mapped;
    }
}
