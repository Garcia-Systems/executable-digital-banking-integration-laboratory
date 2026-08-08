<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/bootstrap.php';
use Harbor\DigitalBankingLab\Capstone\{CapstoneOutcome,EndToEndLaboratory};
use Harbor\DigitalBankingLab\Composition\LaboratoryApplicationFactory;
$root=dirname(__DIR__,2);$failures=0;$tests=[];
$test=static function(string $name,callable $body)use(&$tests):void{$tests[$name]=$body;};
$assert=static function(bool $ok,string $message='assertion failed'):void{if(!$ok)throw new RuntimeException($message);};
$make=static fn()=>new EndToEndLaboratory(new LaboratoryApplicationFactory($root));
$test('successful system composition preserves identities and amounts',function()use($make,$assert):void{$r=$make()->run();$assert($r->outcome===CapstoneOutcome::PASS);$assert($r->member?->id->value==='member-0001');$assert(array_map(fn($a)=>$a->id->value,$r->member->accounts)===['account-0001','account-0002']);$assert($r->activityProfile?->classification->value==='RECENTLY_ACTIVE');$assert($r->verification?->status->value==='VERIFIED');$assert($r->transferPreview?->sourceAvailableBalance->minorUnits===238575);$assert($r->transferPreview?->projectedAvailableBalance->minorUnits===188575);});
$test('preview is non-mutating when executed twice',function()use($make,$assert):void{$first=$make()->run()->transferPreview;$second=$make()->run()->transferPreview;$assert($first?->sourceAvailableBalance->minorUnits===238575&&$second?->sourceAvailableBalance->minorUnits===238575);$assert($first?->previewId==='preview-0001'&&$second?->previewId==='preview-0001');});
$test('independent fixed-time runs are equivalent',function()use($make,$assert):void{$assert(serialize($make()->run())===serialize($make()->run()));});
$test('analytics is allow-listed and financially minimized',function()use($make,$assert):void{$json=json_encode($make()->run()->safeAnalyticsEvents,JSON_THROW_ON_ERROR);foreach(['238575','50000','Move to savings','NS-','HC-','CV-'] as $term)$assert(!str_contains($json,$term),$term);});
$test('representative failures are expected and safely worded',function()use($make,$assert):void{foreach(['northstar-timeout','heritage-unavailable','verification-review','malformed-vendor-response','invalid-transfer','frontend-contract-drift'] as $scenario){$r=$make()->run($scenario);$assert($r->outcome===CapstoneOutcome::EXPECTED_FAILURE,$scenario);foreach(['NS-CUST','HC-100','CV-SUBJECT','token'] as $term)$assert(!str_contains($r->safeFailure,$term));}});
foreach($tests as $name=>$body){try{$body();echo "PASS {$name}\n";}catch(Throwable $e){$failures++;fwrite(STDERR,"FAIL {$name}: {$e->getMessage()}\n");}}
echo "\n".count($tests)." capstone tests, {$failures} failures\n";exit($failures===0?0:1);
