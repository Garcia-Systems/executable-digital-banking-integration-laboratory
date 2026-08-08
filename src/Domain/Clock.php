<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
