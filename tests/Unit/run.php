<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';
require dirname(__DIR__) . '/Support/HarborFakes.php';

use Harbor\DigitalBankingLab\Application\{AccountBalanceGateway, DigitalBankingGateway, IntegrationFailure, IntegrationFailureCategory, MemberVerificationGateway, PreviewTransfer, PreviewTransferCommand};
use Harbor\DigitalBankingLab\Domain\Member\{AccountId, AccountType, MemberId, Money};
use Harbor\DigitalBankingLab\Domain\SequenceIdGenerator;
use Harbor\DigitalBankingLab\Integration\Northstar\{NorthstarClient, NorthstarDigitalBankingAdapter, NorthstarTranslator};
use Harbor\DigitalBankingLab\Integration\Northstar\Exception\NorthstarTimeoutFailure;
use Harbor\DigitalBankingLab\Integration\Northstar\Model\{NorthstarCustomer, NorthstarCustomerKey, NorthstarCustomerStatus, NorthstarProduct, NorthstarProductClass, NorthstarProductKey, NorthstarProductState};
use Harbor\DigitalBankingLab\Integration\VendorIdentityMap;
use Harbor\DigitalBankingLab\Tests\Support\{FakeDigitalBankingGateway, FakeMemberVerificationGateway, MemberFixtureBuilder, RecordingAccountBalanceGateway};

$tests=[]; $test=static function(string $name, callable $body)use(&$tests):void{$tests[$name]=$body;};
$assert=static function(bool $condition,string $message='Assertion failed'):void{if(!$condition)throw new RuntimeException($message);};

$test('it_subtracts_minor_units_without_infrastructure', function()use($assert):void {
    $projected=Money::usd(238575)->subtract(Money::usd(50000));
    $assert($projected->minorUnits===188575 && $projected->format()==='$1,885.75');
});

$test('it_previews_a_transfer_with_three_harbor_owned_fakes', function()use($assert):void {
    $digital=new FakeDigitalBankingGateway(MemberFixtureBuilder::avery());
    $balances=new RecordingAccountBalanceGateway();
    $verification=new FakeMemberVerificationGateway();
    $assert($digital instanceof DigitalBankingGateway && $balances instanceof AccountBalanceGateway && $verification instanceof MemberVerificationGateway);
    $preview=(new PreviewTransfer($digital,$balances,new SequenceIdGenerator('preview-'),$verification))->execute(
        new PreviewTransferCommand(new MemberId('member-0001'),new AccountId('account-0001'),new AccountId('account-0002'),Money::usd(50000),'Fixture memo')
    );
    $assert($preview->projectedAvailableBalance->minorUnits===188575);
    $assert(array_map(static fn(AccountId $id)=>$id->value,$balances->requested)===['account-0001'],'Only the source Harbor AccountId should cross the balance port.');
});

$customer=static fn()=>new NorthstarCustomer(new NorthstarCustomerKey('NS-CUST-4417'),NorthstarCustomerStatus::ENABLED,'Avery Morgan',[
    new NorthstarProduct(new NorthstarProductKey('NS-PROD-9001'),new NorthstarProductClass(NorthstarProductClass::DDA),'Everyday Checking',238575,NorthstarProductState::ACTIVE)
]);
$test('it_maps_northstar_dda_to_harbor_checking', function()use($assert,$customer):void {
    $client=new class($customer()) implements NorthstarClient { public function __construct(private NorthstarCustomer $value){} public function findCustomer(NorthstarCustomerKey $key):NorthstarCustomer{return $this->value;} };
    $member=(new NorthstarDigitalBankingAdapter($client,VendorIdentityMap::laboratory(),new NorthstarTranslator()))->findMember(new MemberId('member-0001'));
    $assert($member->accounts[0]->type===AccountType::CHECKING);
});
$test('it_translates_northstar_timeout_to_harbor_failure', function()use($assert):void {
    $client=new class implements NorthstarClient { public function findCustomer(NorthstarCustomerKey $key):NorthstarCustomer{throw new NorthstarTimeoutFailure('controlled timeout');} };
    try{(new NorthstarDigitalBankingAdapter($client,VendorIdentityMap::laboratory(),new NorthstarTranslator()))->findMember(new MemberId('member-0001'));$assert(false);}
    catch(IntegrationFailure $failure){$assert($failure->category===IntegrationFailureCategory::TIMEOUT);}
});
$test('it_builds_deterministic_member_fixtures',function()use($assert):void{$assert(serialize(MemberFixtureBuilder::avery())===serialize(MemberFixtureBuilder::avery()));});
$test('chapter_18_commands_are_byte_stable',function()use($assert):void{
    $root=dirname(__DIR__,2);$inventory=shell_exec("$root/bin/digital-banking-lab test-inventory");$again=shell_exec("$root/bin/digital-banking-lab test-inventory");
    $determinism=shell_exec("$root/bin/digital-banking-lab test-determinism");$assert($inventory===$again&&str_contains((string)$inventory,'Domain units')&&str_contains((string)$determinism,'Result: PASS'));
});

$failures=0;foreach($tests as $name=>$body){try{$body();echo "PASS {$name}\n";}catch(Throwable $error){$failures++;fwrite(STDERR,"FAIL {$name}: {$error->getMessage()}\n");}}
echo sprintf("\n%d unit tests, %d failures\n",count($tests),$failures);exit($failures===0?0:1);
