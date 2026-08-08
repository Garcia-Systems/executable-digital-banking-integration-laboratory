<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar\Model;
final readonly class NorthstarCustomer
{
    /** @param non-empty-list<NorthstarProduct> $products */
    public function __construct(
        public NorthstarCustomerKey $customerKey,
        public NorthstarCustomerStatus $customerStatus,
        public string $fullName,
        public array $products,
    ) {}
}
