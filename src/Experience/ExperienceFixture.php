<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Experience;
final class ExperienceFixture {
    /** @return list<AnalyticsEvent> */ public static function events():array{
        $events=[];$id=0;$at=new \DateTimeImmutable('2026-01-01T09:00:00Z');
        $add=static function(int $session,string $name,DeviceClass $device,array $properties=[])use(&$events,&$id,$at):void{$events[]=new AnalyticsEvent(sprintf('event-%06d',++$id),sprintf('session-%04d',$session),$name,$at->modify("+{$id} minutes"),$device,'member-web',$properties);};
        for($s=1;$s<=100;$s++){$device=$s<=60?DeviceClass::MOBILE:DeviceClass::DESKTOP;$add($s,'page_view',$device);if(($device===DeviceClass::MOBILE&&$s<=50)||$device===DeviceClass::DESKTOP)$add($s,'member_summary_loaded',$device);
            $viewed=($device===DeviceClass::MOBILE&&$s<=45)||$device===DeviceClass::DESKTOP;if($viewed){$add($s,'navigation_selected',$device,['destination'=>'transfer']);$add($s,'transfer_section_viewed',$device);}
            $started=($device===DeviceClass::MOBILE&&$s<=40)||$device===DeviceClass::DESKTOP;if($started)$add($s,'transfer_preview_started',$device);
            $submitted=($device===DeviceClass::MOBILE&&$s<=34)||$device===DeviceClass::DESKTOP;if($submitted)$add($s,'transfer_preview_submitted',$device);
            $success=($device===DeviceClass::MOBILE&&$s<=30)||($device===DeviceClass::DESKTOP&&$s<=92);if($success)$add($s,'transfer_preview_succeeded',$device);
        }
        foreach(['amount_format','amount_format','amount_format','same_account','same_account','same_account','insufficient_available_balance','verification_required'] as $i=>$category){$s=[31,32,33,34,93,94,95,96][$i];$add($s,'transfer_preview_validation_failed',$s<=60?DeviceClass::MOBILE:DeviceClass::DESKTOP,['error_category'=>$category]);}
        return $events;
    }
}
