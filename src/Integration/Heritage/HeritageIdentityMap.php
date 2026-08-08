<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Heritage;

use Harbor\DigitalBankingLab\Domain\Member\AccountId;
use Harbor\DigitalBankingLab\Integration\{UnknownVendorIdentity};
use Harbor\DigitalBankingLab\Integration\Heritage\Model\HeritageAccountNumber;

final readonly class HeritageIdentityMap
{
    /** @param array<string, HeritageAccountNumber> $accounts */
    public function __construct(private array $accounts) {}
    public static function laboratory(): self
    {
        return new self(['account-0001' => new HeritageAccountNumber('HC-100045'), 'account-0002' => new HeritageAccountNumber('HC-100046')]);
    }
    public function heritageAccountFor(AccountId $id): HeritageAccountNumber
    {
        return $this->accounts[$id->value] ?? throw new UnknownVendorIdentity("No Heritage account mapping for Harbor account: {$id->value}");
    }
}
