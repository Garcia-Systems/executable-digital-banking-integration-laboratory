<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar\Model;
final readonly class NorthstarProduct
{
    public function __construct(
        public NorthstarProductKey $productKey,
        public NorthstarProductClass $productClass,
        public string $nickname,
        public int $currentBalanceCents,
        public NorthstarProductState $state,
    ) { if ($nickname === '') throw new \InvalidArgumentException('Northstar nickname must not be empty.'); }
}
