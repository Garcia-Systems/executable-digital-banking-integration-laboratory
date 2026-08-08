<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
final readonly class ActivityReviewCandidate
{
    public function __construct(public MemberId $memberId, public string $displayName, public ActivityClassification $classification, public ?\DateTimeImmutable $mostRecentActivityAt, public ?int $daysSinceLastActivity, public string $reason) {}
}
