<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\MemberId;

/** A factual repository projection. It deliberately contains no Harbor decision. */
final readonly class MemberActivityFacts
{
    public function __construct(
        public MemberId $memberId,
        public string $displayName,
        public int $accountCount,
        public int $totalActivityCount,
        public int $recentActivityCount,
        public ?\DateTimeImmutable $mostRecentActivityAt,
    ) {}
}
