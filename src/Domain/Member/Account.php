<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain\Member;

final readonly class Account
{
    public function __construct(
        public AccountId $id,
        public AccountType $type,
        public string $displayName,
        public Money $balance,
        public AccountStatus $status,
    ) {
        if ($displayName === '') {
            throw new \InvalidArgumentException('Account display name must not be empty.');
        }
    }
}
