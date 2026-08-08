<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

final class SequenceIdGenerator implements IdGenerator
{
    public function __construct(
        private readonly string $prefix = 'member-',
        private readonly int $startingAt = 1,
        private readonly int $width = 4,
        private int $generated = 0,
    ) {
        if ($prefix === '' || $startingAt < 0 || $width < 1) {
            throw new \InvalidArgumentException('Sequence identifier configuration is invalid.');
        }
    }

    public function nextId(): string
    {
        $identifier = $this->peekNextId();
        ++$this->generated;
        return $identifier;
    }

    public function peekNextId(): string
    {
        return $this->prefix . str_pad((string) ($this->startingAt + $this->generated), $this->width, '0', STR_PAD_LEFT);
    }
}
