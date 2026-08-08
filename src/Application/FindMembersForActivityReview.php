<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
final readonly class FindMembersForActivityReview
{
    public function __construct(private MemberActivityRepository $repository, private ActivityPolicy $policy, private ClassifyMemberActivity $classifier = new ClassifyMemberActivity()) {}
    /** @return list<ActivityReviewCandidate> */
    public function execute(\DateTimeImmutable $asOf): array
    {
        $out=[];
        foreach($this->repository->activityFacts($this->policy->cutoff($asOf)) as $facts){
            $classification=$this->classifier->classify($facts,$this->policy,$asOf);
            if($classification===ActivityClassification::RECENTLY_ACTIVE) continue;
            $days=$facts->mostRecentActivityAt===null?null:max(0,(int)floor(($asOf->getTimestamp()-$facts->mostRecentActivityAt->getTimestamp())/86400));
            $reason=$classification===ActivityClassification::NEVER_ACTIVE?'No activity records exist in the Harbor operational dataset':"No activity within the {$this->policy->inactiveAfterDays}-day laboratory window";
            $out[]=new ActivityReviewCandidate($facts->memberId,$facts->displayName,$classification,$facts->mostRecentActivityAt,$days,$reason);
        }
        return $out;
    }
}
