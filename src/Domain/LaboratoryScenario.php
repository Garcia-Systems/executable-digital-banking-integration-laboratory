<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

final readonly class LaboratoryScenario
{
    public function __construct(
        public string $identifier,
        public string $displayName,
        public string $fixedTime,
        public string $description,
    ) {
        if ($identifier === '' || $displayName === '' || $description === '') {
            throw new \InvalidArgumentException('Scenario fields must not be empty.');
        }
    }
}
