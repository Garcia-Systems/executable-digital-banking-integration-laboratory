<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Fixtures;

use Harbor\DigitalBankingLab\Domain\Member\{Account, AccountId, AccountStatus, AccountType, Member, MemberId, MembershipStatus, Money};

final class MemberFixtureFactory
{
    public static function create(): Member
    {
        return new Member(
            new MemberId('member-0001'),
            'Avery Morgan',
            MembershipStatus::ACTIVE,
            [
                new Account(new AccountId('account-0001'), AccountType::CHECKING, 'Everyday Checking', Money::usd(245075), AccountStatus::OPEN),
                new Account(new AccountId('account-0002'), AccountType::SAVINGS, 'Primary Savings', Money::usd(812000), AccountStatus::OPEN),
            ],
        );
    }

    public static function find(MemberId $id): Member
    {
        $member = self::create();
        if ($member->id->equals($id)) {
            return $member;
        }

        throw new \InvalidArgumentException("Unknown member: {$id->value}");
    }
}
