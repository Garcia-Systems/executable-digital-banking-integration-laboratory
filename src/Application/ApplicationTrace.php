<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

/** Small deterministic teaching aid, deliberately not a production logger. */
final class ApplicationTrace
{
    /** @var list<string> */
    private array $steps = [];
    public function record(string $step): void { $this->steps[] = $step; }
    /** @return list<string> */
    public function steps(): array { return $this->steps; }
}
