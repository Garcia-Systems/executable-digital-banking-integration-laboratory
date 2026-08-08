<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain\Member;

final readonly class MemberId
{
    public function __construct(public string $value)
    {
        if (preg_match('/\Amember-[0-9]{4}\z/D', $value) !== 1) throw new \InvalidArgumentException('Member ID must use the Harbor member-0000 format.');
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
