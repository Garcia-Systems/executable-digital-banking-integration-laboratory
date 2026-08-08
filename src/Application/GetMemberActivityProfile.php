<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;
use Harbor\DigitalBankingLab\Domain\Member\MemberId;
final readonly class GetMemberActivityProfile
{
    public function __construct(private MemberActivityRepository $repository, private ActivityPolicy $policy, private ClassifyMemberActivity $classifier = new ClassifyMemberActivity()) {}
    public function execute(MemberId $memberId, \DateTimeImmutable $asOf): MemberActivityProfile
    {
        $facts=$this->repository->activityFactsFor($memberId,$this->policy->cutoff($asOf));
        if ($facts===null) throw new MemberNotFound("Member {$memberId->value} was not found.");
        $days=$facts->mostRecentActivityAt===null?null:max(0,(int)floor(($asOf->getTimestamp()-$facts->mostRecentActivityAt->getTimestamp())/86400));
        return new MemberActivityProfile($facts->memberId,$facts->displayName,$facts->accountCount,$facts->totalActivityCount,$facts->recentActivityCount,$facts->mostRecentActivityAt,$days,$this->classifier->classify($facts,$this->policy,$asOf),$this->policy,$asOf);
    }
}
