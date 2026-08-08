<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Api;

use Harbor\DigitalBankingLab\Domain\Member\{AccountStatus, AccountType, Member, MembershipStatus};

/** Defines Harbor's public scalar contract instead of serializing domain objects. */
final class MemberSummaryPresenter
{
    /** @return array<string, mixed> */
    public function present(Member $member): array
    {
        return [
            'memberId' => $member->id->value,
            'name' => $member->name,
            'status' => match ($member->status) {
                MembershipStatus::ACTIVE => 'active',
                MembershipStatus::RESTRICTED => 'restricted',
            },
            'accounts' => array_map(static fn ($account): array => [
                'accountId' => $account->id->value,
                'displayName' => $account->displayName,
                'type' => match ($account->type) {
                    AccountType::CHECKING => 'checking',
                    AccountType::SAVINGS => 'savings',
                },
                'balance' => [
                    'currency' => $account->balance->currency,
                    'minorUnits' => $account->balance->minorUnits,
                    'formatted' => $account->balance->format(),
                ],
                'status' => match ($account->status) {
                    AccountStatus::OPEN => 'open',
                    AccountStatus::CLOSED => 'closed',
                },
            ], $member->accounts),
        ];
    }
}
