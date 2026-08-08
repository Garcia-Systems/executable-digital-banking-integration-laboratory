<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\LaboratoryContext;

final class LaboratoryRenderer
{
    public function render(LaboratoryContext $context): string
    {
        return implode("\n", [
            "Scenario: {$context->scenario->identifier}",
            "Time: {$context->clock->now()->format('Y-m-d\\TH:i:s\\Z')}",
            "Vendor: {$context->vendor->displayName}",
            "Vendor status: {$context->vendor->status->value}",
            "Next generated ID: {$context->idGenerator->peekNextId()}",
        ]) . "\n";
    }
}
