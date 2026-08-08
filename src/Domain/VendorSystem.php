<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

final readonly class VendorSystem
{
    public function __construct(
        public string $identifier,
        public string $displayName,
        public SystemOwnership $ownership,
        public VendorStatus $status,
    ) {
        if ($identifier === '' || $displayName === '') {
            throw new \InvalidArgumentException('Vendor system fields must not be empty.');
        }
    }
}
