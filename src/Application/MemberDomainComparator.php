<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\Member;

final class MemberDomainComparator
{
    public function equivalent(Member $left, Member $right): bool
    {
        if ($left->id->value !== $right->id->value || $left->name !== $right->name || $left->status !== $right->status) {
            return false;
        }
        if (count($left->accounts) !== count($right->accounts)) {
            return false;
        }
        foreach ($left->accounts as $index => $account) {
            $other = $right->accounts[$index];
            if ($account->id->value !== $other->id->value
                || $account->displayName !== $other->displayName
                || $account->type !== $other->type
                || $account->status !== $other->status
                || $account->balance->currency !== $other->balance->currency
                || $account->balance->minorUnits !== $other->balance->minorUnits) {
                return false;
            }
        }

        return true;
    }
}
