<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain\Member;

final readonly class AccountId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Account ID must not be empty.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
