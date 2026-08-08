<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Debug;

/** A deterministic, debug-only fault laboratory. */
final readonly class DebugScenario
{
    /** @param list<string> $trace @param array<string, string> $detail */
    public function __construct(
        public string $id,
        public string $title,
        public string $symptom,
        public string $journey,
        public string $faultComponent,
        public string $diagnosis,
        public string $setup,
        public string $request,
        public string $response,
        public array $trace,
        public string $firstDivergence,
        public array $detail,
        public string $regressionTest,
    ) {
        if ($id === '' || $faultComponent === '' || $trace === []) {
            throw new \InvalidArgumentException('A debug scenario requires one named fault and a boundary trace.');
        }
    }
}
