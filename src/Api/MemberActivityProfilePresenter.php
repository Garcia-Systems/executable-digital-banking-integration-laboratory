<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Api;
use Harbor\DigitalBankingLab\Application\MemberActivityProfile;
final class MemberActivityProfilePresenter
{
    /** @return array<string,mixed> */
    public function present(MemberActivityProfile $profile): array
    {
        return ['memberId'=>$profile->memberId->value,'name'=>$profile->displayName,'asOf'=>$profile->asOf->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),'policy'=>['inactiveAfterDays'=>$profile->policy->inactiveAfterDays],'facts'=>['accountCount'=>$profile->accountCount,'activityCount'=>$profile->totalActivityCount,'recentActivityCount'=>$profile->recentActivityCount,'mostRecentActivityAt'=>$profile->mostRecentActivityAt?->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),'daysSinceLastActivity'=>$profile->daysSinceLastActivity],'classification'=>strtolower($profile->classification->value)];
    }
}
