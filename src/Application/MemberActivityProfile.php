<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
final readonly class MemberActivityProfile
{
    public function __construct(public MemberId $memberId, public string $displayName, public int $accountCount, public int $totalActivityCount, public int $recentActivityCount, public ?\DateTimeImmutable $mostRecentActivityAt, public ?int $daysSinceLastActivity, public ActivityClassification $classification, public ActivityPolicy $policy, public \DateTimeImmutable $asOf) {}
}
