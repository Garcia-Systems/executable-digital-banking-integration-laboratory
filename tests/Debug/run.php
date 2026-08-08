<?php
declare(strict_types=1);

require dirname(__DIR__,2).'/bootstrap.php';

use Harbor\DigitalBankingLab\Debug\{DebugApplicationFactory,DebugScenarioCatalog,DebugScenarioRenderer};

$required=['frontend-stale-member','api-contract-drift','northstar-new-product-class','northstar-malformed-json','heritage-soap-fault','heritage-malformed-xml','sql-inactivity-predicate','sql-missing-never-active-member','transfer-verification-review','money-decimal-conversion','public-vendor-id-leak','orchestration-account-mismatch'];
$catalog=new DebugScenarioCatalog();$factory=new DebugApplicationFactory($catalog);$renderer=new DebugScenarioRenderer();$failures=0;
$check=static function(bool $ok,string $name)use(&$failures):void{echo ($ok?'PASS ':'FAIL ').$name."\n";if(!$ok)$failures++;};
$all=$catalog->all();$ids=array_map(static fn($s)=>$s->id,$all);
$check($ids===$required,'catalog IDs and ordering are stable');
$check(count($ids)===count(array_unique($ids)),'scenario IDs are unique');
$check(array_reduce($all,static fn($ok,$s)=>$ok&&$s->symptom!==''&&$s->journey!==''&&$s->faultComponent!=='',true),'every scenario has symptom, journey, and one primary fault');
foreach($all as $scenario){
    $run=$renderer->run($factory->scenario($scenario->id));$trace=$renderer->trace($scenario);$detail=$renderer->detail($scenario);
    $check(str_contains($run,$scenario->symptom)||str_contains($run,$scenario->response),'documented symptom is observable: '.$scenario->id);
    $check(str_contains($trace,'[FAIL]')&&str_contains($trace,$scenario->firstDivergence),'trace locates first divergence: '.$scenario->id);
    $check($run===$renderer->run($factory->scenario($scenario->id)),'run is deterministic: '.$scenario->id);
    $check(!preg_match('/password|bearer|memo/i',$trace.$detail),'diagnostics exclude secrets, tokens, and memo: '.$scenario->id);
}
try{$catalog->find('unknown');$check(false,'unknown scenario fails clearly');}catch(InvalidArgumentException $e){$check(str_contains($e->getMessage(),'Unknown debug scenario'),'unknown scenario fails clearly');}
$normal=\Harbor\DigitalBankingLab\Http\HttpKernelFactory::create()->dispatch('GET','/api/members/member-0001');
$check($normal->status===200&&str_contains($normal->body,'displayName')&&!str_contains($normal->body,'NS-CUST'),'normal HTTP factory remains correct and minimized');
$check($factory->scenario('api-contract-drift')->faultComponent!==$factory->scenario('northstar-malformed-json')->faultComponent,'scenario fixtures do not contaminate each other');
echo "\n".count($all)." debug scenarios, {$failures} failures\n";exit($failures===0?0:1);
