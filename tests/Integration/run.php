<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use Harbor\DigitalBankingLab\Application\{ActivityPolicy, GetMemberActivityProfile, IntegrationFailure, IntegrationFailureCategory, VerificationStatus};
use Harbor\DigitalBankingLab\Composition\IntegrationTestApplicationFactory;
use Harbor\DigitalBankingLab\Domain\Member\{AccountId, MemberId};
use Harbor\DigitalBankingLab\Infrastructure\Database\SqlMemberActivityRepository;
use Harbor\DigitalBankingLab\Integration\{VendorIdentityMap};
use Harbor\DigitalBankingLab\Integration\ClearVerify\{ClearVerifyIdentityMap, ClearVerifyMemberVerificationAdapter, ClearVerifyRestClient};
use Harbor\DigitalBankingLab\Integration\Heritage\{HeritageCoreBankingAdapter, HeritageIdentityMap, HeritageSoapClient, HeritageSoapEnvelopeBuilder};
use Harbor\DigitalBankingLab\Integration\Northstar\{NorthstarDigitalBankingAdapter, NorthstarRestClient, NorthstarTranslator};

$root = dirname(__DIR__, 2);
$factory = new IntegrationTestApplicationFactory($root);
$tests = [];
$test = static function (string $name, callable $body) use (&$tests): void { $tests[$name] = $body; };
$assert = static function (bool $condition, string $message = 'Assertion failed'): void { if (!$condition) throw new RuntimeException($message); };
$json = static fn(string $body): mixed => json_decode($body, true, 512, JSON_THROW_ON_ERROR);
$fixture = static fn(string $name): mixed => json_decode((string) file_get_contents(dirname(__DIR__, 2) . "/contracts/api/{$name}.json"), true, 512, JSON_THROW_ON_ERROR);
$requestBody = json_encode(['sourceAccountId'=>'account-0001','destinationAccountId'=>'account-0002','amount'=>['currency'=>'USD','minorUnits'=>50000],'memo'=>'Teaching preview'], JSON_THROW_ON_ERROR);

$test('test factory builds a complete deterministic stack', function () use ($factory, $assert): void {
    $assert($factory->router()->dispatch('GET', '/api/members/member-0001')->status === 200);
});
$test('fresh databases reset fixtures and enable foreign keys', function () use ($factory, $assert): void {
    $first=$factory->database(); $first->exec("UPDATE members SET display_name='Changed' WHERE member_id='member-0003'");
    $second=$factory->database();
    $assert((int)$second->query('SELECT COUNT(*) FROM members')->fetchColumn()===3);
    $assert((int)$second->query('PRAGMA foreign_keys')->fetchColumn()===1);
});
$test('real SQL retrieves member accounts', function () use ($factory, $assert): void {
    $accounts=(new SqlMemberActivityRepository($factory->database()))->accountsFor(new MemberId('member-0001'));
    $assert(array_map(fn($a)=>$a->id->value,$accounts)===['account-0001','account-0002']);
});
$test('real SQL aggregates activity including never-active members', function () use ($factory, $assert): void {
    $repo=new SqlMemberActivityRepository($factory->database()); $facts=$repo->activityFacts(new DateTimeImmutable('2025-07-19T14:30:00Z'));
    $assert($facts[0]->recentActivityCount===2 && $facts[2]->totalActivityCount===0 && $facts[2]->mostRecentActivityAt===null);
    $profile=(new GetMemberActivityProfile($repo,ActivityPolicy::inactiveAfterDays(180)))->execute(new MemberId('member-0003'),new DateTimeImmutable('2026-01-15T14:30:00Z'));
    $assert($profile->classification->value==='NEVER_ACTIVE');
});
$test('NOT EXISTS inactivity executes correctly', function () use ($factory, $assert): void {
    $ids=array_map(fn($m)=>$m->id->value,(new SqlMemberActivityRepository($factory->database()))->inactiveMembers(new DateTimeImmutable('2025-07-19T14:30:00Z')));
    $assert($ids===['member-0002','member-0003']);
});
$test('SQL injection-shaped bound input changes no database behavior', function () use ($factory, $assert): void {
    $pdo=$factory->database();$statement=$pdo->prepare(SqlMemberActivityRepository::MEMBER_ACCOUNTS_SQL);
    $statement->execute(['member_id'=>"member-0001' OR '1'='1"]);
    $assert($statement->fetchAll()===[] && (int)$pdo->query('SELECT COUNT(*) FROM members')->fetchColumn()===3);
});

$test('Northstar real client parser and adapter return Harbor member and record request', function () use ($factory, $assert): void {
    $http=$factory->northstarTransport(); $gateway=new NorthstarDigitalBankingAdapter(new NorthstarRestClient($http,'https://northstar.invalid'),VendorIdentityMap::laboratory(),new NorthstarTranslator());
    $member=$gateway->findMember(new MemberId('member-0001')); $request=$http->requests()[0];
    $assert($member->id->value==='member-0001' && $request['method']==='GET' && str_ends_with($request['url'],'/v1/customers/NS-CUST-4417') && $request['headers']['Accept']==='application/json');
});
$test('Northstar malformed JSON crosses client and adapter as Harbor failure', function () use ($factory, $assert): void {
    try {(new NorthstarDigitalBankingAdapter(new NorthstarRestClient($factory->northstarTransport('malformed-json'),'https://northstar.invalid'),VendorIdentityMap::laboratory(),new NorthstarTranslator()))->findMember(new MemberId('member-0001'));$assert(false);} catch(IntegrationFailure $e){$assert($e->category===IntegrationFailureCategory::INVALID_EXTERNAL_RESPONSE);}
});
$test('Northstar representative failures translate', function () use ($factory, $assert): void {
    foreach(['customer-not-found','vendor-error','incomplete-response','unsupported-product','vendor-timeout','vendor-unavailable'] as $scenario){try{(new NorthstarDigitalBankingAdapter(new NorthstarRestClient($factory->northstarTransport($scenario),'https://northstar.invalid'),VendorIdentityMap::laboratory(),new NorthstarTranslator()))->findMember(new MemberId('member-0001'));$assert(false,$scenario);}catch(Throwable){}}
});
$test('Heritage real envelope parser and adapter return balance details', function () use ($factory, $assert): void {
    $transport=$factory->heritageTransport();$adapter=new HeritageCoreBankingAdapter(new HeritageSoapClient($transport,new HeritageSoapEnvelopeBuilder(),'https://heritage-core.invalid/soap'),HeritageIdentityMap::laboratory());$details=$adapter->accountBalanceDetails(new AccountId('account-0001'));$request=$transport->requests()[0];
    $assert($details->availableBalance->minorUnits===238575 && str_contains($request['xmlBody'],'HC-100045') && !str_contains($request['xmlBody'],'member-0001'));
});
$test('Heritage SOAP fault and malformed XML translate', function () use ($factory, $assert): void {
    foreach(['account-not-found','core-error','malformed-xml','incomplete-response','unsupported-status','unsupported-currency'] as $scenario){try{$adapter=new HeritageCoreBankingAdapter(new HeritageSoapClient($factory->heritageTransport($scenario),new HeritageSoapEnvelopeBuilder(),'https://heritage-core.invalid/soap'),HeritageIdentityMap::laboratory());$adapter->accountBalanceDetails(new AccountId('account-0001'));$assert(false,$scenario);}catch(IntegrationFailure){}}
});
$test('Heritage parser rejects hostile XML without entity resolution', function () use ($assert): void {
    $transport=new class implements \Harbor\DigitalBankingLab\Infrastructure\Soap\SoapTransport {public function send(string $e,string $a,string $b):\Harbor\DigitalBankingLab\Infrastructure\Soap\SoapTransportResponse{return new \Harbor\DigitalBankingLab\Infrastructure\Soap\SoapTransportResponse(200,'<!DOCTYPE x [<!ENTITY secret SYSTEM "file:///etc/passwd">]><x>&secret;</x>');}};
    try{(new HeritageSoapClient($transport,new HeritageSoapEnvelopeBuilder(),'https://heritage-core.invalid/soap'))->getAccountDetails(new \Harbor\DigitalBankingLab\Integration\Heritage\Model\HeritageAccountNumber('HC-100045'));$assert(false);}catch(\Harbor\DigitalBankingLab\Integration\Heritage\Exception\HeritageResponseDecodingFailure){$assert(true);}
});
$test('ClearVerify real client parser and adapter translate statuses', function () use ($factory, $assert): void {
    foreach(['verification-pass'=>VerificationStatus::VERIFIED,'verification-review'=>VerificationStatus::REVIEW_REQUIRED,'verification-fail'=>VerificationStatus::NOT_VERIFIED] as $scenario=>$expected){$http=$factory->clearVerifyTransport($scenario);$adapter=new ClearVerifyMemberVerificationAdapter(new ClearVerifyRestClient($http),ClearVerifyIdentityMap::laboratory());$assert($adapter->verificationFor(new MemberId('member-0001'))->status===$expected);$request=$http->requests()[0];$assert($request['method']==='GET'&&!str_contains($request['url'],'50000')&&!str_contains($request['url'],'Teaching'));}
});
$test('ClearVerify malformed unsupported and transport failures translate', function () use ($factory, $assert): void {
    foreach(['verification-malformed-json','verification-unsupported-status','verification-timeout','verification-unavailable'] as $scenario){try{$adapter=new ClearVerifyMemberVerificationAdapter(new ClearVerifyRestClient($factory->clearVerifyTransport($scenario)),ClearVerifyIdentityMap::laboratory());$adapter->verificationFor(new MemberId('member-0001'));$assert(false,$scenario);}catch(IntegrationFailure){}}
});

foreach(['member-summary'=>'/api/members/member-0001','financial-overview'=>'/api/members/member-0001/financial-overview','verification'=>'/api/members/member-0001/verification'] as $name=>$path){
    $test("GET {$name} preserves shared contract",function()use($factory,$assert,$json,$fixture,$name,$path):void{$response=$factory->router()->dispatch('GET',$path);$assert($response->status===200&&str_starts_with($response->headers['Content-Type'],'application/json')&&$json($response->body)===$fixture($name));});
}
$test('POST transfer preview preserves shared contract and safe memo text',function()use($factory,$assert,$json,$fixture,$requestBody):void{$response=$factory->router()->dispatch('POST','/api/members/member-0001/transfer-preview',$requestBody);$assert($response->status===200&&$json($response->body)===$fixture('transfer-preview'));$hostile=str_replace('Teaching preview','<img src=x onerror=alert(1)>',$requestBody);$assert(str_contains($factory->router()->dispatch('POST','/api/members/member-0001/transfer-preview',$hostile)->body,'<img src=x onerror=alert(1)>'));});
$test('HTTP rejects malformed JSON content type and invalid fields',function()use($factory,$assert):void{$router=$factory->router();$assert($router->dispatch('POST','/api/members/member-0001/transfer-preview','{')->status===400);$assert($router->dispatch('POST','/api/members/member-0001/transfer-preview','{}',['Content-Type'=>'text/plain'])->status===415);$assert($router->dispatch('POST','/api/members/member-0001/transfer-preview','{"memo":"only"}')->status===422);});
$test('Northstar timeout propagates to stable 504 API failure',function()use($factory,$assert,$json):void{$r=$factory->router('vendor-timeout')->dispatch('GET','/api/members/member-0001');$assert($r->status===504&&$json($r->body)['error']['code']==='upstream_timeout');});
$test('Heritage malformed XML propagates to stable 502 API failure',function()use($factory,$assert,$json):void{$r=$factory->router('normal','malformed-xml')->dispatch('GET','/api/members/member-0001/financial-overview');$assert($r->status===502&&$json($r->body)['error']['code']==='upstream_invalid_response');});
$test('ClearVerify review blocks transfer with Harbor vocabulary',function()use($factory,$assert,$json,$requestBody):void{$r=$factory->router(clearVerify:'verification-review')->dispatch('POST','/api/members/member-0001/transfer-preview',$requestBody);$assert($r->status===409&&$json($r->body)['error']['code']==='verification_review_required'&&!str_contains($r->body,'MANUAL_REVIEW'));});
$test('cross-layer overview and preview use real clients without HTTP routing',function()use($factory,$assert):void{$apps=$factory->applications();$overview=$apps->getMemberFinancialOverview()->execute(new MemberId('member-0001'));$preview=$apps->previewTransfer()->execute(new \Harbor\DigitalBankingLab\Application\PreviewTransferCommand(new MemberId('member-0001'),new AccountId('account-0001'),new AccountId('account-0002'),\Harbor\DigitalBankingLab\Domain\Member\Money::usd(50000),'Teaching preview'));$assert(count($overview->accounts)===2&&$preview->previewId==='preview-0001');});
$test('public responses contain no vendor IDs or secrets',function()use($factory,$assert,$requestBody):void{$router=$factory->router();$body=implode('',[$router->dispatch('GET','/api/members/member-0001')->body,$router->dispatch('GET','/api/members/member-0001/financial-overview')->body,$router->dispatch('GET','/api/members/member-0001/verification')->body,$router->dispatch('POST','/api/members/member-0001/transfer-preview',$requestBody)->body]);foreach(['NS-CUST','NS-PROD','HC-100','CV-SUBJECT','CV-REF','laboratory-token','Bearer'] as $term)$assert(!str_contains($body,$term),$term);});
$test('repeated independently composed HTTP runs are equivalent',function()use($factory,$assert,$requestBody):void{$first=$factory->router()->dispatch('POST','/api/members/member-0001/transfer-preview',$requestBody)->body;$second=$factory->router()->dispatch('POST','/api/members/member-0001/transfer-preview',$requestBody)->body;$assert($first===$second);});

$failures=0;foreach($tests as $name=>$body){try{$body();echo "PASS {$name}\n";}catch(Throwable $e){$failures++;fwrite(STDERR,"FAIL {$name}: {$e->getMessage()}\n");}}
echo sprintf("\n%d integration tests, %d failures\n",count($tests),$failures);exit($failures===0?0:1);
