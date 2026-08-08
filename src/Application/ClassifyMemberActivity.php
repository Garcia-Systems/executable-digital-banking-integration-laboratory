<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
final class ClassifyMemberActivity
{
    public function classify(MemberActivityFacts $facts, ActivityPolicy $policy, \DateTimeImmutable $asOf): ActivityClassification
    {
        // Policy and as-of are explicit inputs; the repository calculated recentCount using policy.cutoff(asOf).
        $policy->cutoff($asOf);
        if ($facts->totalActivityCount === 0) return ActivityClassification::NEVER_ACTIVE;
        return $facts->recentActivityCount > 0 ? ActivityClassification::RECENTLY_ACTIVE : ActivityClassification::INACTIVE;
    }
}
