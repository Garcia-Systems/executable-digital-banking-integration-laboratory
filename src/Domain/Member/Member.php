<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Domain\Member;

final readonly class Member
{
    /** @param non-empty-list<Account> $accounts */
    public function __construct(
        public MemberId $id,
        public string $name,
        public MembershipStatus $status,
        public array $accounts,
    ) {
        if ($name === '') {
            throw new \InvalidArgumentException('Member name must not be empty.');
        }
        if ($accounts === []) {
            throw new \InvalidArgumentException('A member must have at least one account.');
        }
        $accountIds = [];
        foreach ($accounts as $account) {
            if (!$account instanceof Account) {
                throw new \InvalidArgumentException('Member accounts must be Account values.');
            }
            if (isset($accountIds[$account->id->value])) {
                throw new \InvalidArgumentException("Duplicate account ID: {$account->id->value}");
            }
            $accountIds[$account->id->value] = true;
        }
    }
}
