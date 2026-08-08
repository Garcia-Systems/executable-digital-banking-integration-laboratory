<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Experience;
final class AnalyzeTransferPreviewFunnel {
    private const STEPS=['Member Web visit'=>'page_view','Member summary loaded'=>'member_summary_loaded','Transfer section viewed'=>'transfer_section_viewed','Transfer preview started'=>'transfer_preview_started','Transfer preview submitted'=>'transfer_preview_submitted','Transfer preview succeeded'=>'transfer_preview_succeeded'];
    /** @param list<AnalyticsEvent> $events */ public function execute(array $events,?DeviceClass $device=null):FunnelResult{$counts=[];foreach(self::STEPS as $label=>$name){$sessions=[];foreach($events as $e)if($e->eventName===$name&&($device===null||$e->device===$device))$sessions[$e->anonymousSessionId]=true;$counts[$label]=count($sessions);}return new FunnelResult($counts);}
}
