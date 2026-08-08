<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

final readonly class IntegrationRelationship
{
    public function __construct(
        public string $sourceSystemId,
        public string $destinationSystemId,
        public string $interactionType,
        public string $purpose,
    ) {
    }
}
