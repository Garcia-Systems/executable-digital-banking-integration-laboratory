<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain;

/** A fictional laboratory actor—not a record containing real member data. */
final readonly class Member
{
    public function __construct(
        public string $identifier,
        public string $displayName,
    ) {
    }
}
