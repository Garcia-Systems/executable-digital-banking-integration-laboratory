<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Fixtures;

use Harbor\DigitalBankingLab\Domain\{FixedClock, LaboratoryContext, LaboratoryScenario, SequenceIdGenerator, SystemOwnership, VendorStatus, VendorSystem};

final class LaboratoryFixtureFactory
{
    /** @return list<string> */
    public static function scenarioIdentifiers(): array
    {
        return ['normal-operation', 'vendor-timeout', 'vendor-unavailable'];
    }

    public static function create(string $scenarioIdentifier): LaboratoryContext
    {
        [$displayName, $description, $status] = match ($scenarioIdentifier) {
            'normal-operation' => ['Normal operation', 'The simulated vendor is ready to serve requests.', VendorStatus::AVAILABLE],
            'vendor-timeout' => ['Vendor timeout', 'The simulated vendor is in a slow state; no real waiting occurs.', VendorStatus::SLOW],
            'vendor-unavailable' => ['Vendor unavailable', 'The simulated vendor is unavailable.', VendorStatus::UNAVAILABLE],
            default => throw new \InvalidArgumentException("Unknown laboratory scenario: {$scenarioIdentifier}"),
        };
        $fixedTime = '2026-01-15T14:30:00Z';
        $scenario = new LaboratoryScenario($scenarioIdentifier, $displayName, $fixedTime, $description);

        return new LaboratoryContext(
            $scenario,
            new FixedClock($fixedTime),
            new SequenceIdGenerator(),
            new VendorSystem('vendor-digital-banking', 'Digital Banking Platform', SystemOwnership::VENDOR, $status),
        );
    }
}
