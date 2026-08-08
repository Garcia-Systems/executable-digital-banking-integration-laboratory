<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Heritage\Model;

final readonly class HeritageAccountDetails
{
    public function __construct(
        public HeritageAccountNumber $accountNumber,
        public string $accountStatus,
        public int $ledgerBalanceMinorUnits,
        public int $availableBalanceMinorUnits,
        public string $currencyCode,
    ) {}
}
