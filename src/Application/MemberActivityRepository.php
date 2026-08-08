<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Domain\Member\{AccountId, MemberId, Money};

interface MemberActivityRepository
{
    /** @return list<OperationalMember> */ public function members(): array;
    /** @return list<OperationalAccount> */ public function accountsFor(MemberId $memberId): array;
    /** @return list<AccountActivity> */ public function activitySince(AccountId $accountId, \DateTimeImmutable $cutoff): array;
    /** @return array<string, ?\DateTimeImmutable> */ public function mostRecentActivityByAccount(): array;
    /** @return list<InactiveMemberSummary> */ public function inactiveMembers(\DateTimeImmutable $cutoff): array;
    public function totalActivityAmount(AccountId $accountId): ?Money;
    /** @return list<MemberActivityFacts> */ public function activityFacts(\DateTimeImmutable $cutoff): array;
    public function activityFactsFor(MemberId $memberId, \DateTimeImmutable $cutoff): ?MemberActivityFacts;
}
