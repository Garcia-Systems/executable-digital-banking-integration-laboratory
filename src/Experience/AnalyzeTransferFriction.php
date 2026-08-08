<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Experience;
final class AnalyzeTransferFriction { /** @param list<AnalyticsEvent> $events @return array<string,int> */ public function execute(array $events):array{$out=['amount_format'=>0,'same_account'=>0,'insufficient_available_balance'=>0,'verification_required'=>0];foreach($events as $e)if($e->eventName==='transfer_preview_validation_failed')$out[$e->properties['error_category']]++;return $out;} }
