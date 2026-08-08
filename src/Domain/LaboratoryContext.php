<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

final readonly class LaboratoryContext
{
    public function __construct(
        public LaboratoryScenario $scenario,
        public Clock $clock,
        public IdGenerator $idGenerator,
        public VendorSystem $vendor,
    ) {
    }
}
