<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

final readonly class FixedClock implements Clock
{
    private \DateTimeImmutable $instant;

    public function __construct(string $instant)
    {
        $this->instant = new \DateTimeImmutable($instant);
    }

    public function now(): \DateTimeImmutable
    {
        return $this->instant;
    }
}
