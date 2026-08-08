<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

/** An explicit laboratory rule, not runtime configuration or regulatory policy. */
final readonly class ActivityPolicy
{
    private function __construct(public int $inactiveAfterDays)
    {
        if ($inactiveAfterDays < 1) throw new \InvalidArgumentException('Activity threshold must be at least one day.');
    }
    public static function inactiveAfterDays(int $days): self { return new self($days); }
    public static function laboratoryDefault(): self { return new self(180); }
    public function cutoff(\DateTimeImmutable $asOf): \DateTimeImmutable { return $asOf->sub(new \DateInterval("P{$this->inactiveAfterDays}D")); }
}
