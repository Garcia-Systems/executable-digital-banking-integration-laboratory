<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

final readonly class DigitalSystem
{
    public function __construct(
        public string $identifier,
        public string $displayName,
        public SystemOwnership $ownership,
        public SystemCategory $category,
        public string $description,
    ) {
        if ($identifier === '' || $displayName === '' || $description === '') {
            throw new \InvalidArgumentException('Digital system fields must not be empty.');
        }
    }
}
