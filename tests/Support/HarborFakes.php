<?php
declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Tests\Support;

use Harbor\DigitalBankingLab\Application\{AccountBalanceGateway, DigitalBankingGateway, MemberVerificationGateway, MemberVerificationResult, VerificationStatus};
use Harbor\DigitalBankingLab\Domain\Member\{Account, AccountBalanceDetails, AccountId, AccountStatus, AccountType, Member, MemberId, MembershipStatus, Money};

final class MemberFixtureBuilder
{
    public static function avery(): Member
    {
        return new Member(new MemberId('member-0001'), 'Avery Morgan', MembershipStatus::ACTIVE, [
            new Account(new AccountId('account-0001'), AccountType::CHECKING, 'Everyday Checking', Money::usd(238575), AccountStatus::OPEN),
            new Account(new AccountId('account-0002'), AccountType::SAVINGS, 'Savings', Money::usd(800000), AccountStatus::OPEN),
        ]);
    }
}

final class FakeDigitalBankingGateway implements DigitalBankingGateway
{
    public function __construct(private readonly Member $member) {}
    public function findMember(MemberId $memberId): Member { return $this->member; }
}

final class RecordingAccountBalanceGateway implements AccountBalanceGateway
{
    /** @var list<AccountId> */ public array $requested = [];
    public function __construct(private readonly int $availableMinorUnits = 238575) {}
    public function accountBalanceDetails(AccountId $accountId): AccountBalanceDetails
    {
        $this->requested[] = $accountId;
        return new AccountBalanceDetails($accountId, Money::usd(240000), Money::usd($this->availableMinorUnits), AccountStatus::OPEN);
    }
}

final class FakeMemberVerificationGateway implements MemberVerificationGateway
{
    public function __construct(private readonly VerificationStatus $status = VerificationStatus::VERIFIED) {}
    public function verificationFor(MemberId $memberId): MemberVerificationResult { return new MemberVerificationResult($memberId, $this->status); }
}
