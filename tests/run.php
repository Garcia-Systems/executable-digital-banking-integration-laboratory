<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Harbor\DigitalBankingLab\Application\EcosystemRenderer;
use Harbor\DigitalBankingLab\Application\LaboratoryRenderer;
use Harbor\DigitalBankingLab\Application\MemberSummaryRenderer;
use Harbor\DigitalBankingLab\Domain\Member\{Account, AccountId, AccountStatus, AccountType, Member, MemberId, MembershipStatus, Money};
use Harbor\DigitalBankingLab\Domain\FixedClock;
use Harbor\DigitalBankingLab\Domain\SequenceIdGenerator;
use Harbor\DigitalBankingLab\Domain\SystemOwnership;
use Harbor\DigitalBankingLab\Domain\VendorStatus;
use Harbor\DigitalBankingLab\Fixtures\HarborEcosystemFixture;
use Harbor\DigitalBankingLab\Fixtures\LaboratoryFixtureFactory;
use Harbor\DigitalBankingLab\Fixtures\MemberFixtureFactory;
use Harbor\DigitalBankingLab\Application\{DigitalBankingGateway, MemberDomainComparator, VendorMemberRenderer};
use Harbor\DigitalBankingLab\Integration\{UnknownVendorIdentity, VendorIdentityMap, VendorTranslationException};
use Harbor\DigitalBankingLab\Integration\Northstar\{DeterministicNorthstarClient, NorthstarDigitalBankingAdapter, NorthstarTranslator};
use Harbor\DigitalBankingLab\Integration\Northstar\Model\{NorthstarCustomer, NorthstarCustomerKey, NorthstarCustomerStatus, NorthstarProductClass};
use Harbor\DigitalBankingLab\Api\MemberSummaryPresenter;
use Harbor\DigitalBankingLab\Application\{GetMemberSummary, MemberNotFound};
use Harbor\DigitalBankingLab\Http\HttpKernelFactory;
use Harbor\DigitalBankingLab\Infrastructure\Http\DeterministicHttpClient;
use Harbor\DigitalBankingLab\Integration\Northstar\NorthstarRestClient;
use Harbor\DigitalBankingLab\Integration\Northstar\Exception\{NorthstarCustomerNotFound, NorthstarHttpFailure, NorthstarResponseDecodingFailure, NorthstarTimeoutFailure, NorthstarUnavailableFailure};
use Harbor\DigitalBankingLab\Application\{AccountBalanceGateway, FailureMatrixRenderer, FailureScenarioLaboratory, GetAccountBalanceDetails, IntegrationCatalog, IntegrationDescriptor, IntegrationFailure, IntegrationFailureCategory, IntegrationFailureReport, RetryDisposition};
use Harbor\DigitalBankingLab\Architecture\ArchitectureInspector;
use Harbor\DigitalBankingLab\Composition\LaboratoryApplicationFactory;
use Harbor\DigitalBankingLab\Domain\Member\AccountBalanceDetails;
use Harbor\DigitalBankingLab\Infrastructure\Soap\DeterministicHeritageSoapTransport;
use Harbor\DigitalBankingLab\Integration\Heritage\{HeritageCoreBankingAdapter, HeritageIdentityMap, HeritageSoapClient, HeritageSoapEnvelopeBuilder};
use Harbor\DigitalBankingLab\Integration\Heritage\Exception\{HeritageAccountNotFound, HeritageCoreError, HeritageResponseDecodingFailure};
use Harbor\DigitalBankingLab\Integration\Heritage\Model\{HeritageAccountDetails, HeritageAccountNumber};
use Harbor\DigitalBankingLab\Api\IntegrationFailureApiMapper;
use Harbor\DigitalBankingLab\Api\MemberFinancialOverviewPresenter;
use Harbor\DigitalBankingLab\Application\{ApplicationTrace, GetMemberFinancialOverview};
use Harbor\DigitalBankingLab\Application\IntegrationOperation;

$tests = [];
$test = static function (string $name, callable $body) use (&$tests): void { $tests[$name] = $body; };
$assert = static function (bool $condition, string $message = 'Assertion failed'): void {
    if (!$condition) { throw new RuntimeException($message); }
};

$test('expected systems exist', function () use ($assert): void {
    $ecosystem = HarborEcosystemFixture::create();
    $expected = ['member-web', 'mobile-banking', 'internal-operations', 'harbor-integration-layer', 'member-database', 'vendor-digital-banking', 'legacy-core', 'fintech-provider'];
    $actual = array_map(fn ($system) => $system->identifier, $ecosystem->systems);
    $assert($actual === $expected, 'Fixture systems or their stable order changed.');
});

$test('ownership boundaries are explicit', function () use ($assert): void {
    $ecosystem = HarborEcosystemFixture::create();
    $assert($ecosystem->system('harbor-integration-layer')->ownership === SystemOwnership::HARBOR);
    $assert($ecosystem->system('vendor-digital-banking')->ownership === SystemOwnership::VENDOR);
    $assert($ecosystem->system('fintech-provider')->ownership === SystemOwnership::THIRD_PARTY);
});

$test('all relationships reference valid systems', function () use ($assert): void {
    $ecosystem = HarborEcosystemFixture::create();
    foreach ($ecosystem->relationships as $relationship) {
        $assert($ecosystem->system($relationship->sourceSystemId)->identifier === $relationship->sourceSystemId);
        $assert($ecosystem->system($relationship->destinationSystemId)->identifier === $relationship->destinationSystemId);
    }
});

$test('integration graph fixture is deterministic', function () use ($assert): void {
    $first = HarborEcosystemFixture::create();
    $second = HarborEcosystemFixture::create();
    $assert(serialize($first) === serialize($second));
});

$test('member web reaches vendor through Harbor integration layer', function () use ($assert): void {
    $path = HarborEcosystemFixture::create()->path('member-web', 'vendor-digital-banking');
    $assert(array_map(fn ($system) => $system->identifier, $path) === ['member-web', 'harbor-integration-layer', 'vendor-digital-banking']);
});

$test('repeated rendering is byte-for-byte identical', function () use ($assert): void {
    $renderer = new EcosystemRenderer();
    $ecosystem = HarborEcosystemFixture::create();
    $assert($renderer->render($ecosystem) === $renderer->render($ecosystem));
    $assert($renderer->renderMemberWebPath($ecosystem) === $renderer->renderMemberWebPath($ecosystem));
});

$test('fixed clock always returns its configured instant', function () use ($assert): void {
    $clock = new FixedClock('2026-01-15T14:30:00Z');
    $assert($clock->now()->format('Y-m-d\\TH:i:s\\Z') === '2026-01-15T14:30:00Z');
    $assert($clock->now() === $clock->now(), 'Repeated clock reads should return the configured immutable instant.');
});

$test('sequence identifiers are predictable', function () use ($assert): void {
    $generator = new SequenceIdGenerator();
    $assert([$generator->nextId(), $generator->nextId(), $generator->nextId()] === ['member-0001', 'member-0002', 'member-0003']);
});

$test('identically configured generators produce identical sequences', function () use ($assert): void {
    $first = new SequenceIdGenerator('case-', 7, 3);
    $second = new SequenceIdGenerator('case-', 7, 3);
    $assert([$first->nextId(), $first->nextId()] === [$second->nextId(), $second->nextId()]);
});

$test('every known laboratory scenario can be constructed', function () use ($assert): void {
    foreach (LaboratoryFixtureFactory::scenarioIdentifiers() as $identifier) {
        $assert(LaboratoryFixtureFactory::create($identifier)->scenario->identifier === $identifier);
    }
});

$test('unknown laboratory scenarios fail explicitly', function () use ($assert): void {
    try {
        LaboratoryFixtureFactory::create('not-a-scenario');
        $assert(false, 'An unknown scenario should not be constructed.');
    } catch (InvalidArgumentException $error) {
        $assert($error->getMessage() === 'Unknown laboratory scenario: not-a-scenario');
    }
});

$test('scenarios map to explicit vendor states', function () use ($assert): void {
    $assert(LaboratoryFixtureFactory::create('normal-operation')->vendor->status === VendorStatus::AVAILABLE);
    $assert(LaboratoryFixtureFactory::create('vendor-timeout')->vendor->status === VendorStatus::SLOW);
    $assert(LaboratoryFixtureFactory::create('vendor-unavailable')->vendor->status === VendorStatus::UNAVAILABLE);
});

$test('laboratory rendering does not consume an identifier', function () use ($assert): void {
    $context = LaboratoryFixtureFactory::create('normal-operation');
    $renderer = new LaboratoryRenderer();
    $assert($renderer->render($context) === $renderer->render($context));
    $assert($context->idGenerator->nextId() === 'member-0001');
});

$test('independently constructed scenarios render identically', function () use ($assert): void {
    $renderer = new LaboratoryRenderer();
    $first = $renderer->render(LaboratoryFixtureFactory::create('normal-operation'));
    $second = $renderer->render(LaboratoryFixtureFactory::create('normal-operation'));
    $assert($first === $second);
    $assert(hash('sha256', $first) === hash('sha256', $second));
});

$test('member and account identifiers preserve typed identity', function () use ($assert): void {
    $assert((new MemberId('member-0001'))->equals(new MemberId('member-0001')));
    $assert(!(new MemberId('member-0001'))->equals(new MemberId('member-0002')));
    $assert((new AccountId('account-0001'))->equals(new AccountId('account-0001')));
});

$test('money uses integer minor units and deterministic USD formatting', function () use ($assert): void {
    $money = Money::usd(125050);
    $assert(is_int($money->minorUnits) && $money->minorUnits === 125050);
    $assert($money->currency === 'USD' && $money->format() === '$1,250.50');
    $assert(Money::usd(-5)->format() === '-$0.05');
});

$test('money addition operates on integer minor units', function () use ($assert): void {
    $sum = Money::usd(245075)->add(Money::usd(812000));
    $assert($sum->minorUnits === 1057075 && is_int($sum->minorUnits));
    $assert($sum->format() === '$10,570.75');
});

$test('member vocabulary is represented by explicit enums', function () use ($assert): void {
    $assert(MembershipStatus::ACTIVE->value === 'ACTIVE' && MembershipStatus::RESTRICTED->value === 'RESTRICTED');
    $assert(AccountType::CHECKING->value === 'CHECKING' && AccountType::SAVINGS->value === 'SAVINGS');
    $assert(AccountStatus::OPEN->value === 'OPEN' && AccountStatus::CLOSED->value === 'CLOSED');
});

$test('deterministic member fixture contains expected accounts', function () use ($assert): void {
    $member = MemberFixtureFactory::create();
    $assert($member->id->value === 'member-0001' && $member->name === 'Avery Morgan');
    $assert($member->status === MembershipStatus::ACTIVE);
    $assert(array_map(fn (Account $account) => [$account->id->value, $account->type, $account->balance->minorUnits], $member->accounts) === [
        ['account-0001', AccountType::CHECKING, 245075],
        ['account-0002', AccountType::SAVINGS, 812000],
    ]);
});

$test('duplicate account identifiers within a member are rejected', function () use ($assert): void {
    $account = new Account(new AccountId('account-0001'), AccountType::CHECKING, 'Checking', Money::usd(0), AccountStatus::OPEN);
    try {
        new Member(new MemberId('member-0001'), 'Fictional Person', MembershipStatus::ACTIVE, [$account, $account]);
        $assert(false, 'Duplicate accounts should not be accepted.');
    } catch (InvalidArgumentException $error) {
        $assert($error->getMessage() === 'Duplicate account ID: account-0001');
    }
});

$test('member summary intentionally renders balances and total', function () use ($assert): void {
    $summary = (new MemberSummaryRenderer())->render(MemberFixtureFactory::create());
    $assert(str_contains($summary, 'Balance: $2,450.75'));
    $assert(str_contains($summary, 'Balance: $8,120.00'));
    $assert(str_contains($summary, 'Total displayed balance: $10,570.75'));
});

$test('unknown member lookup fails explicitly', function () use ($assert): void {
    try {
        MemberFixtureFactory::find(new MemberId('member-9999'));
        $assert(false, 'An unknown member should not be returned.');
    } catch (InvalidArgumentException $error) {
        $assert($error->getMessage() === 'Unknown member: member-9999');
    }
});

$test('independent member fixtures render byte-for-byte identically', function () use ($assert): void {
    $renderer = new MemberSummaryRenderer();
    $assert($renderer->render(MemberFixtureFactory::create()) === $renderer->render(MemberFixtureFactory::create()));
});

$northstarGateway = static function (string $scenario = 'normal'): DigitalBankingGateway {
    return new NorthstarDigitalBankingAdapter(new DeterministicNorthstarClient($scenario), VendorIdentityMap::laboratory(), new NorthstarTranslator());
};

$test('Northstar models preserve vendor terminology', function () use ($assert): void {
    $customer = (new DeterministicNorthstarClient())->findCustomer(new NorthstarCustomerKey('NS-CUST-4417'));
    $assert($customer instanceof NorthstarCustomer && $customer->customerStatus === NorthstarCustomerStatus::ENABLED);
    $assert($customer->products[0]->productClass->value === 'DDA');
    $assert($customer->products[1]->productClass->value === 'SAV');
});

$test('vendor identity map keeps typed namespaces distinct', function () use ($assert): void {
    $map = VendorIdentityMap::laboratory();
    $assert($map->northstarCustomerFor(new MemberId('member-0001'))->value === 'NS-CUST-4417');
    $assert($map->northstarProductFor(new AccountId('account-0001'))->value === 'NS-PROD-9001');
    $assert($map->northstarProductFor(new AccountId('account-0002'))->value === 'NS-PROD-9002');
    $assert($map->harborAccountFor($map->northstarProductFor(new AccountId('account-0001')))->value === 'account-0001');
});

$test('Northstar terminology translates explicitly to Harbor values', function () use ($assert): void {
    $translator = new NorthstarTranslator();
    $assert($translator->membershipStatus(NorthstarCustomerStatus::ENABLED) === MembershipStatus::ACTIVE);
    $assert($translator->accountType(new NorthstarProductClass('DDA')) === AccountType::CHECKING);
    $assert($translator->accountType(new NorthstarProductClass('SAV')) === AccountType::SAVINGS);
});

$test('Northstar adapter returns complete Harbor domain state', function () use ($assert, $northstarGateway): void {
    $member = $northstarGateway()->findMember(new MemberId('member-0001'));
    $assert($member instanceof Member && !$member instanceof NorthstarCustomer);
    $assert($member->id->value === 'member-0001' && $member->id->value !== 'NS-CUST-4417');
    $assert(count($member->accounts) === 2 && $member->accounts[0]->id->value === 'account-0001');
    $assert($member->accounts[0]->type === AccountType::CHECKING && $member->accounts[1]->type === AccountType::SAVINGS);
    $assert($member->accounts[0]->status === AccountStatus::OPEN && $member->accounts[1]->status === AccountStatus::OPEN);
    $assert($member->accounts[0]->balance->minorUnits === 245075 && is_int($member->accounts[0]->balance->minorUnits));
    $assert($member->accounts[0]->balance->add($member->accounts[1]->balance)->format() === '$10,570.75');
});

$test('unsupported Northstar values fail at translation boundary', function () use ($assert, $northstarGateway): void {
    try { $northstarGateway('northstar-unsupported-product')->findMember(new MemberId('member-0001')); $assert(false); }
    catch (IntegrationFailure $error) { $assert($error->category === IntegrationFailureCategory::UNSUPPORTED_EXTERNAL_VALUE); }
    $assert(array_column(AccountType::cases(), 'value') === ['CHECKING', 'SAVINGS']);
});

$test('unknown identity mappings fail explicitly', function () use ($assert, $northstarGateway): void {
    try { $northstarGateway()->findMember(new MemberId('member-9999')); $assert(false); }
    catch (UnknownVendorIdentity $error) { $assert($error->getMessage() === 'No Northstar customer mapping for Harbor member: member-9999'); }
});

$test('repeated vendor translation is meaningfully equivalent and renders identically', function () use ($assert, $northstarGateway): void {
    $first = $northstarGateway()->findMember(new MemberId('member-0001'));
    $second = $northstarGateway()->findMember(new MemberId('member-0001'));
    $assert((new MemberDomainComparator())->equivalent($first, $second));
    $renderer = new VendorMemberRenderer();
    $assert($renderer->render($first) === $renderer->render($second));
});

$test('Harbor member domain does not depend on Northstar namespace', function () use ($assert): void {
    foreach (glob(dirname(__DIR__) . '/src/Domain/Member/*.php') as $file) {
        $assert(!str_contains((string) file_get_contents($file), 'Northstar'), "Northstar dependency leaked into {$file}");
    }
});

$test('GetMemberSummary uses its Harbor gateway without HTTP', function () use ($assert): void {
    $calledWith = null;
    $gateway = new class($calledWith) implements DigitalBankingGateway {
        public function __construct(private mixed &$calledWith) {}
        public function findMember(MemberId $memberId): Member { $this->calledWith = $memberId; return MemberFixtureFactory::create(); }
    };
    $member = (new GetMemberSummary($gateway))->execute(new MemberId('member-0001'));
    $assert($member instanceof Member && $calledWith instanceof MemberId && $calledWith->value === 'member-0001');
});

$test('GetMemberSummary exposes unknown members as an application outcome', function () use ($assert, $northstarGateway): void {
    try { (new GetMemberSummary($northstarGateway()))->execute(new MemberId('member-9999')); $assert(false); }
    catch (MemberNotFound $error) { $assert($error->getMessage() === 'Member was not found.'); }
});

$test('member API presenter matches the intentional contract fixture', function () use ($assert): void {
    $actual = (new MemberSummaryPresenter())->present(MemberFixtureFactory::create());
    $expected = json_decode((string) file_get_contents(__DIR__ . '/Fixtures/api/member-0001.json'), true, 512, JSON_THROW_ON_ERROR);
    $assert($actual === $expected);
    $json = json_encode($actual, JSON_THROW_ON_ERROR);
    $assert(!str_contains($json, 'Northstar') && !str_contains($json, 'customerKey') && !str_contains($json, 'productKey'));
    $assert(!str_contains($json, 'DDA') && !str_contains($json, 'SAV'));
    $assert(is_int($actual['accounts'][0]['balance']['minorUnits']));
});

$test('HTTP member route returns deterministic JSON', function () use ($assert): void {
    $router = HttpKernelFactory::create();
    $first = $router->dispatch('GET', '/api/members/member-0001');
    $second = $router->dispatch('GET', '/api/members/member-0001');
    $body = json_decode($first->body, true, 512, JSON_THROW_ON_ERROR);
    $assert($first->status === 200 && $first->headers['Content-Type'] === 'application/json; charset=utf-8');
    $assert($body['memberId'] === 'member-0001' && array_column($body['accounts'], 'accountId') === ['account-0001', 'account-0002']);
    $assert($first->body === $second->body);
    $assert(!str_contains($first->body, 'Northstar') && !str_contains($first->body, 'Trace'));
});

$test('HTTP errors use the stable safe error contract', function () use ($assert): void {
    $router = HttpKernelFactory::create();
    foreach ([
        ['GET', '/api/members/member-9999', 404, 'member_not_found'],
        ['GET', '/api/members/', 400, 'invalid_member_id'],
        ['POST', '/api/members/member-0001', 405, 'method_not_allowed'],
        ['GET', '/api/unknown', 404, 'route_not_found'],
    ] as [$method, $path, $status, $code]) {
        $response = $router->dispatch($method, $path);
        $body = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        $assert($response->status === $status && array_keys($body) === ['error']);
        $assert(array_keys($body['error']) === ['code', 'message'] && $body['error']['code'] === $code);
        $assert(!str_contains($response->body, 'Northstar') && !str_contains($response->body, 'Exception') && !str_contains($response->body, '/workspace/'));
    }
});

$restClient = static function (string $scenario = 'normal', ?DeterministicHttpClient &$transport = null): NorthstarRestClient {
    $transport = new DeterministicHttpClient($scenario, dirname(__DIR__) . '/fixtures/northstar');
    return new NorthstarRestClient($transport, 'https://northstar.invalid');
};

$test('Northstar REST client constructs the explicit GET request', function () use ($assert, $restClient): void {
    $client = $restClient('normal', $transport);
    $client->findCustomer(new NorthstarCustomerKey('NS-CUST-4417'));
    $request = $transport->requests()[0];
    $assert($request['method'] === 'GET');
    $assert($request['url'] === 'https://northstar.invalid/v1/customers/NS-CUST-4417');
    $assert($request['headers'] === ['Accept' => 'application/json']);
});

$test('Northstar REST client decodes typed customer products and integer balances', function () use ($assert, $restClient): void {
    $customer = $restClient()->findCustomer(new NorthstarCustomerKey('NS-CUST-4417'));
    $assert($customer instanceof NorthstarCustomer && $customer->customerKey->value === 'NS-CUST-4417');
    $assert(array_map(fn ($product) => $product->productClass->value, $customer->products) === ['DDA', 'SAV']);
    $assert($customer->products[0]->currentBalanceCents === 245075 && is_int($customer->products[0]->currentBalanceCents));
});

$test('Northstar REST client classifies HTTP status failures', function () use ($assert, $restClient): void {
    try { $restClient('customer-not-found')->findCustomer(new NorthstarCustomerKey('NS-CUST-4417')); $assert(false); }
    catch (NorthstarCustomerNotFound $error) { $assert($error->statusCode === 404); }
    try { $restClient('vendor-error')->findCustomer(new NorthstarCustomerKey('NS-CUST-4417')); $assert(false); }
    catch (NorthstarHttpFailure $error) { $assert($error->statusCode === 500); }
});

$test('Northstar REST client rejects malformed and incomplete JSON', function () use ($assert, $restClient): void {
    foreach (['malformed-json', 'incomplete-response'] as $scenario) {
        try { $restClient($scenario)->findCustomer(new NorthstarCustomerKey('NS-CUST-4417')); $assert(false); }
        catch (NorthstarResponseDecodingFailure $error) { $assert(str_contains($error->getMessage(), 'Northstar')); }
    }
});

$test('Northstar REST transport distinguishes immediate timeout and unavailability', function () use ($assert, $restClient): void {
    $started = hrtime(true);
    try { $restClient('vendor-timeout')->findCustomer(new NorthstarCustomerKey('NS-CUST-4417')); $assert(false); }
    catch (NorthstarTimeoutFailure $error) { $assert(str_contains($error->getMessage(), 'timed out')); }
    $assert((hrtime(true) - $started) < 100_000_000, 'Deterministic timeout must not wait.');
    try { $restClient('vendor-unavailable')->findCustomer(new NorthstarCustomerKey('NS-CUST-4417')); $assert(false); }
    catch (NorthstarUnavailableFailure $error) { $assert(str_contains($error->getMessage(), 'unavailable')); }
});

$test('Northstar adapter works through REST while preserving Harbor identities and meaning', function () use ($assert, $restClient): void {
    $gateway = new NorthstarDigitalBankingAdapter($restClient(), VendorIdentityMap::laboratory(), new NorthstarTranslator());
    $member = $gateway->findMember(new MemberId('member-0001'));
    $assert((new MemberDomainComparator())->equivalent(MemberFixtureFactory::create(), $member));
    $serialized = serialize($member);
    $assert(!str_contains($serialized, 'NS-CUST') && !str_contains($serialized, 'NS-PROD') && !str_contains($serialized, 'HTTP'));
});

$test('unsupported REST product remains a semantic translation failure', function () use ($assert, $restClient): void {
    $gateway = new NorthstarDigitalBankingAdapter($restClient('unsupported-product'), VendorIdentityMap::laboratory(), new NorthstarTranslator());
    try { $gateway->findMember(new MemberId('member-0001')); $assert(false); }
    catch (IntegrationFailure $error) { $assert($error->category === IntegrationFailureCategory::UNSUPPORTED_EXTERNAL_VALUE); }
});

$test('REST-backed Harbor API preserves the Chapter 4 contract and hides vendor transport', function () use ($assert): void {
    $response = HttpKernelFactory::create()->dispatch('GET', '/api/members/member-0001');
    $actual = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
    $expected = json_decode((string) file_get_contents(__DIR__ . '/Fixtures/api/member-0001.json'), true, 512, JSON_THROW_ON_ERROR);
    $assert($response->status === 200 && $actual === $expected);
    foreach (['NS-CUST', 'NS-PROD', 'northstar.invalid', 'Accept', 'DeterministicHttpClient', 'NorthstarRestClient'] as $detail) $assert(!str_contains($response->body, $detail));
});

$heritageClient = static function (string $scenario = 'normal', ?DeterministicHeritageSoapTransport &$transport = null): HeritageSoapClient {
    $transport = new DeterministicHeritageSoapTransport($scenario);
    return new HeritageSoapClient($transport, new HeritageSoapEnvelopeBuilder(), 'https://heritage-core.invalid/soap');
};

$test('SOAP request is deterministic, namespaced, identified, and safely escaped', function () use ($assert): void {
    $builder = new HeritageSoapEnvelopeBuilder();
    $first = $builder->getAccountDetails(new HeritageAccountNumber('HC-100045'));
    $assert($first === $builder->getAccountDetails(new HeritageAccountNumber('HC-100045')));
    $assert(str_contains($first, 'xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"'));
    $assert(str_contains($first, '<her:GetAccountDetailsRequest>') && str_contains($first, '>HC-100045</her:AccountNumber>'));
    $escaped = $builder->getAccountDetails(new HeritageAccountNumber('HC-&<"'));
    $assert(str_contains($escaped, 'HC-&amp;&lt;&quot;') && !str_contains($escaped, 'HC-&<'));
});

$test('SOAP client decodes Heritage details and preserves integer minor units', function () use ($assert, $heritageClient): void {
    $client = $heritageClient('normal', $transport);
    $details = $client->getAccountDetails(new HeritageAccountNumber('HC-100045'));
    $assert($details instanceof HeritageAccountDetails && $details->accountNumber->value === 'HC-100045');
    $assert($details->ledgerBalanceMinorUnits === 245075 && is_int($details->ledgerBalanceMinorUnits));
    $assert($details->availableBalanceMinorUnits === 238575 && $details->currencyCode === 'USD');
    $assert(count($transport->requests()) === 1 && $transport->requests()[0]['endpoint'] === 'https://heritage-core.invalid/soap');
});

$test('HTTP 200 SOAP faults are classified by SOAP meaning', function () use ($assert, $heritageClient): void {
    try { $heritageClient('account-not-found')->getAccountDetails(new HeritageAccountNumber('HC-X')); $assert(false); }
    catch (HeritageAccountNotFound $error) { $assert($error->httpStatus === 200 && str_contains($error->faultCode, 'ACCOUNT_NOT_FOUND')); }
    try { $heritageClient('core-error')->getAccountDetails(new HeritageAccountNumber('HC-X')); $assert(false); }
    catch (HeritageCoreError $error) { $assert($error->httpStatus === 200 && str_contains($error->faultCode, 'CORE_ERROR')); }
});

$test('SOAP client distinguishes malformed, incomplete, and unexpected XML', function () use ($assert, $heritageClient): void {
    foreach (['malformed-xml' => 'malformed XML', 'incomplete-response' => "AvailableBalanceMinorUnits", 'unexpected-operation' => 'GetAccountDetailsResponse'] as $scenario => $message) {
        try { $heritageClient($scenario)->getAccountDetails(new HeritageAccountNumber('HC-X')); $assert(false); }
        catch (HeritageResponseDecodingFailure $error) { $assert(str_contains($error->getMessage(), $message)); }
    }
});

$test('Heritage identity mapping remains a distinct namespace', function () use ($assert): void {
    $map = HeritageIdentityMap::laboratory();
    $assert($map->heritageAccountFor(new AccountId('account-0001'))->value === 'HC-100045');
    $assert($map->heritageAccountFor(new AccountId('account-0002'))->value === 'HC-100046');
    try { $map->heritageAccountFor(new AccountId('account-9999')); $assert(false); }
    catch (UnknownVendorIdentity $error) { $assert(str_contains($error->getMessage(), 'account-9999')); }
});

$test('Heritage adapter translates to focused Harbor values without leakage', function () use ($assert, $heritageClient): void {
    $adapter = new HeritageCoreBankingAdapter($heritageClient(), HeritageIdentityMap::laboratory());
    $details = $adapter->accountBalanceDetails(new AccountId('account-0001'));
    $assert($details instanceof AccountBalanceDetails && $details->accountId->value === 'account-0001');
    $assert($details->ledgerBalance->minorUnits === 245075 && $details->availableBalance->minorUnits === 238575);
    $assert($details->status === AccountStatus::OPEN && !str_contains(serialize($details), 'HC-100045'));
    $assert(!str_contains(serialize($details), 'soap') && !str_contains(serialize($details), 'XML'));
});

$test('Heritage adapter rejects unsupported status and currency', function () use ($assert, $heritageClient): void {
    foreach (['unsupported-status' => 'AccountStatus', 'unsupported-currency' => 'CurrencyCode'] as $scenario => $word) {
        try { (new HeritageCoreBankingAdapter($heritageClient($scenario), HeritageIdentityMap::laboratory()))->accountBalanceDetails(new AccountId('account-0001')); $assert(false); }
        catch (IntegrationFailure $error) { $assert($error->category === IntegrationFailureCategory::UNSUPPORTED_EXTERNAL_VALUE && str_contains($error->diagnosticCode, strtoupper(str_replace('AccountStatus', 'STATUS', str_replace('CurrencyCode', 'CURRENCY', $word))))); }
    }
});

$test('GetAccountBalanceDetails depends only on a Harbor-owned gateway', function () use ($assert): void {
    $gateway = new class implements AccountBalanceGateway {
        public function accountBalanceDetails(AccountId $id): AccountBalanceDetails { return new AccountBalanceDetails($id, Money::usd(1), Money::usd(1), AccountStatus::CLOSED); }
    };
    $details = (new GetAccountBalanceDetails($gateway))->execute(new AccountId('account-test'));
    $assert($details->accountId->value === 'account-test' && $details->ledgerBalance->minorUnits === 1);
});

$test('Harbor domain and application source do not depend on Heritage SOAP or XML', function () use ($assert): void {
    foreach ([dirname(__DIR__) . '/src/Domain', dirname(__DIR__) . '/src/Application'] as $directory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') continue;
            $source = (string) file_get_contents($file->getPathname());
            foreach (['Integration\\Heritage', 'Infrastructure\\Soap', 'DOMDocument', 'SimpleXML', 'soap:'] as $forbidden) $assert(!str_contains($source, $forbidden), "Boundary leak in {$file->getPathname()}: {$forbidden}");
        }
    }
});

$test('integration catalog describes known integrations in stable order', function () use ($assert): void {
    $catalog = new IntegrationCatalog();
    $first = $catalog->all();
    $second = $catalog->all();
    $assert(array_map(fn (IntegrationDescriptor $item) => $item->id, $first) === ['northstar-digital-banking', 'heritage-core-banking']);
    $assert(serialize($first) === serialize($second));
    $assert($catalog->find('unknown') === null);
});

$test('integration descriptors preserve transport and Harbor result differences', function () use ($assert): void {
    $catalog = new IntegrationCatalog();
    $northstar = $catalog->find('northstar-digital-banking');
    $heritage = $catalog->find('heritage-core-banking');
    $assert($northstar?->transport === 'REST / HTTP' && $northstar->encoding === 'JSON' && $northstar->harborResult === 'Member');
    $assert($heritage?->transport === 'SOAP' && $heritage->encoding === 'XML' && $heritage->harborResult === 'AccountBalanceDetails');
    $assert($northstar->port === 'DigitalBankingGateway' && $heritage->port === 'AccountBalanceGateway');
    $assert($northstar->identityMappingRequired && $heritage->identityMappingRequired);
});

$test('Harbor-owned ports expose only typed Harbor inputs and outputs', function () use ($assert): void {
    foreach ([DigitalBankingGateway::class, AccountBalanceGateway::class] as $port) {
        foreach ((new ReflectionClass($port))->getMethods() as $method) {
            $types = [$method->getReturnType(), ...array_map(fn (ReflectionParameter $parameter) => $parameter->getType(), $method->getParameters())];
            foreach ($types as $type) {
                $name = $type instanceof ReflectionNamedType ? $type->getName() : '';
                $assert(str_starts_with($name, 'Harbor\\DigitalBankingLab\\Domain\\'), "Transport type leaked through {$port}");
                foreach (['Guzzle', 'array', 'json', 'soap', 'xml', 'DOMDocument', 'SimpleXML'] as $forbidden) $assert(stripos($name, $forbidden) === false);
            }
        }
    }
});

$test('composition root wires adapters to their distinct Harbor capabilities', function () use ($assert): void {
    $factory = new LaboratoryApplicationFactory(dirname(__DIR__));
    $assert($factory->digitalBankingGateway() instanceof DigitalBankingGateway);
    $assert($factory->digitalBankingGateway() instanceof NorthstarDigitalBankingAdapter);
    $assert($factory->accountBalanceGateway() instanceof AccountBalanceGateway);
    $assert($factory->accountBalanceGateway() instanceof HeritageCoreBankingAdapter);
});

$test('member use case is unchanged when composition swaps Northstar clients', function () use ($assert): void {
    $factory = new LaboratoryApplicationFactory(dirname(__DIR__));
    $restMember = $factory->getMemberSummary(true)->execute(new MemberId('member-0001'));
    $directMember = $factory->getMemberSummary(false)->execute(new MemberId('member-0001'));
    $assert((new MemberDomainComparator())->equivalent($restMember, $directMember));
});

$test('Chapter 7 architecture inspection rules pass', function () use ($assert): void {
    $inspection = new ArchitectureInspector(dirname(__DIR__) . '/src');
    $assert(count($inspection->checks()) === 6);
    $assert($inspection->passes(), $inspection->render());
});

$test('Chapter 8 taxonomy and laboratory retry rules are explicit', function () use ($assert): void {
    $assert(array_column(IntegrationFailureCategory::cases(), 'value') === ['NOT_FOUND', 'TEMPORARY_UNAVAILABLE', 'TIMEOUT', 'EXTERNAL_ERROR', 'INVALID_EXTERNAL_RESPONSE', 'UNSUPPORTED_EXTERNAL_VALUE']);
    $assert(array_column(RetryDisposition::cases(), 'value') === ['RETRYABLE', 'NOT_RETRYABLE', 'UNKNOWN']);
    $laboratory = new FailureScenarioLaboratory(new LaboratoryApplicationFactory(dirname(__DIR__)));
    $assert($laboratory->run('northstar-timeout')->retryDisposition === RetryDisposition::RETRYABLE);
    foreach (['northstar-not-found', 'northstar-malformed-json', 'northstar-unsupported-product'] as $scenario) $assert($laboratory->run($scenario)->retryDisposition === RetryDisposition::NOT_RETRYABLE);
});

$test('Northstar failures are translated explicitly before leaving its adapter', function () use ($assert): void {
    $laboratory = new FailureScenarioLaboratory(new LaboratoryApplicationFactory(dirname(__DIR__)));
    $expected = [
        'northstar-timeout' => IntegrationFailureCategory::TIMEOUT, 'northstar-unavailable' => IntegrationFailureCategory::TEMPORARY_UNAVAILABLE,
        'northstar-not-found' => IntegrationFailureCategory::NOT_FOUND, 'northstar-http-error' => IntegrationFailureCategory::EXTERNAL_ERROR,
        'northstar-malformed-json' => IntegrationFailureCategory::INVALID_EXTERNAL_RESPONSE, 'northstar-incomplete-response' => IntegrationFailureCategory::INVALID_EXTERNAL_RESPONSE,
        'northstar-unsupported-product' => IntegrationFailureCategory::UNSUPPORTED_EXTERNAL_VALUE,
    ];
    foreach ($expected as $scenario => $category) { $failure = $laboratory->run($scenario); $assert($failure::class === IntegrationFailure::class && $failure->category === $category); }
});

$test('Heritage failures are translated explicitly before leaving its adapter', function () use ($assert): void {
    $laboratory = new FailureScenarioLaboratory(new LaboratoryApplicationFactory(dirname(__DIR__)));
    $expected = [
        'heritage-account-not-found' => IntegrationFailureCategory::NOT_FOUND, 'heritage-core-error' => IntegrationFailureCategory::EXTERNAL_ERROR,
        'heritage-malformed-xml' => IntegrationFailureCategory::INVALID_EXTERNAL_RESPONSE, 'heritage-incomplete-response' => IntegrationFailureCategory::INVALID_EXTERNAL_RESPONSE,
        'heritage-unsupported-status' => IntegrationFailureCategory::UNSUPPORTED_EXTERNAL_VALUE, 'heritage-unsupported-currency' => IntegrationFailureCategory::UNSUPPORTED_EXTERNAL_VALUE,
    ];
    foreach ($expected as $scenario => $category) { $failure = $laboratory->run($scenario); $assert($failure::class === IntegrationFailure::class && $failure->category === $category); }
});

$test('public failure mapping is contextual, stable, and member safe', function () use ($assert): void {
    $laboratory = new FailureScenarioLaboratory(new LaboratoryApplicationFactory(dirname(__DIR__))); $mapper = new IntegrationFailureApiMapper();
    $expected = ['northstar-not-found' => [404, 'member_not_found'], 'heritage-account-not-found' => [404, 'account_not_found'], 'northstar-timeout' => [504, 'upstream_timeout'], 'northstar-unavailable' => [503, 'service_temporarily_unavailable'], 'northstar-malformed-json' => [502, 'upstream_invalid_response'], 'heritage-unsupported-status' => [502, 'upstream_invalid_response']];
    foreach ($expected as $scenario => [$status, $code]) {
        $mapping = $mapper->map($laboratory->run($scenario)); $json = json_encode($mapping['error']->toArray(), JSON_THROW_ON_ERROR);
        $assert($mapping['status'] === $status && $mapping['error']->code === $code);
        foreach (['Northstar', 'Heritage', 'SOAP', 'NORTHSTAR_', 'HERITAGE_', 'Avery Morgan', '245075', '<soap:', 'customerKey', 'Bearer', 'stack trace'] as $secret) $assert(!str_contains($json, $secret));
    }
});

$test('operator diagnostics and failure matrix are complete and deterministic', function () use ($assert): void {
    $laboratory = new FailureScenarioLaboratory(new LaboratoryApplicationFactory(dirname(__DIR__))); $reporter = new IntegrationFailureReport();
    $first = $reporter->render('northstar-timeout', $laboratory->run('northstar-timeout'));
    $assert($first === $reporter->render('northstar-timeout', $laboratory->run('northstar-timeout')));
    foreach (['External system: Northstar Digital Banking', 'Harbor failure category: TIMEOUT', 'Retry disposition: RETRYABLE', 'Diagnostic code: NORTHSTAR_TIMEOUT', 'Public API mapping: 504 upstream_timeout'] as $line) $assert(str_contains($first, $line));
    $matrix = (new FailureMatrixRenderer())->render($laboratory);
    $assert(substr_count($matrix, "\n") === count(FailureScenarioLaboratory::SCENARIOS) + 2 && str_contains($matrix, 'heritage-unsupported-currency | UNSUPPORTED_EXTERNAL_VALUE | NOT_RETRYABLE | 502 | upstream_invalid_response'));
});

$test('member financial overview orchestrates Harbor ports sequentially and returns typed results', function () use ($assert): void {
    $events = [];
    $member = MemberFixtureFactory::find(new MemberId('member-0001'));
    $digital = new class($member, $events) implements DigitalBankingGateway {
        public function __construct(private Member $member, private array &$events) {}
        public function findMember(MemberId $id): Member { $this->events[] = "member:{$id->value}"; return $this->member; }
    };
    $balances = new class($events) implements AccountBalanceGateway {
        public function __construct(private array &$events) {}
        public function accountBalanceDetails(AccountId $id): AccountBalanceDetails {
            $this->events[] = "balance:{$id->value}";
            return new AccountBalanceDetails($id, Money::usd($id->value === 'account-0001' ? 245075 : 812000), Money::usd($id->value === 'account-0001' ? 238575 : 812000), AccountStatus::OPEN);
        }
    };
    $trace = new ApplicationTrace();
    $first = (new GetMemberFinancialOverview($digital, $balances, $trace))->execute($member->id);
    $assert($events === ['member:member-0001', 'balance:account-0001', 'balance:account-0002']);
    $assert($first->member === $member && count($first->accounts) === 2);
    $assert($first->accounts[0]->account->balance->minorUnits === 245075 && $first->accounts[0]->ledgerBalance->minorUnits === 245075 && $first->accounts[0]->availableBalance->minorUnits === 238575);
    $assert($first->accounts[1]->ledgerBalance->minorUnits === 812000 && $first->accounts[1]->availableBalance->minorUnits === 812000);
    $assert($trace->steps() === ['DigitalBankingGateway.getMember(member-0001)', 'Returned Member with 2 accounts', 'AccountBalanceGateway.getDetails(account-0001)', 'AccountBalanceGateway.getDetails(account-0002)', 'Construct MemberFinancialOverview', 'Return application result']);
});

$test('overview rejects mismatched account identity', function () use ($assert): void {
    $member = MemberFixtureFactory::find(new MemberId('member-0001'));
    $digital = new class($member) implements DigitalBankingGateway { public function __construct(private Member $member) {} public function findMember(MemberId $id): Member { return $this->member; } };
    $balances = new class implements AccountBalanceGateway { public function accountBalanceDetails(AccountId $id): AccountBalanceDetails { return new AccountBalanceDetails(new AccountId('account-9999'), Money::usd(1), Money::usd(1), AccountStatus::OPEN); } };
    try { (new GetMemberFinancialOverview($digital, $balances))->execute($member->id); $assert(false); } catch (UnexpectedValueException $error) { $assert(str_contains($error->getMessage(), 'identity')); }
});

$test('overview fails whole result and stops sequential work on Harbor integration failure', function () use ($assert): void {
    $events = []; $member = MemberFixtureFactory::find(new MemberId('member-0001'));
    $digital = new class($member) implements DigitalBankingGateway { public function __construct(private Member $member) {} public function findMember(MemberId $id): Member { return $this->member; } };
    $balances = new class($events) implements AccountBalanceGateway {
        public function __construct(private array &$events) {}
        public function accountBalanceDetails(AccountId $id): AccountBalanceDetails {
            $this->events[] = $id->value;
            if ($id->value === 'account-0002') throw new IntegrationFailure(IntegrationFailureCategory::NOT_FOUND, RetryDisposition::NOT_RETRYABLE, 'A required external service failed.', 'hidden', IntegrationOperation::ACCOUNT_BALANCE_LOOKUP, 'HIDDEN');
            return new AccountBalanceDetails($id, Money::usd(245075), Money::usd(238575), AccountStatus::OPEN);
        }
    };
    try { (new GetMemberFinancialOverview($digital, $balances))->execute($member->id); $assert(false); }
    catch (IntegrationFailure $failure) { $assert($failure->category === IntegrationFailureCategory::NOT_FOUND && $events === ['account-0001', 'account-0002']); }
});

$test('financial overview presenter is explicit and contains no vendor language', function () use ($assert): void {
    $overview = (new LaboratoryApplicationFactory(dirname(__DIR__)))->getMemberFinancialOverview()->execute(new MemberId('member-0001'));
    $data = (new MemberFinancialOverviewPresenter())->present($overview); $json = json_encode($data, JSON_THROW_ON_ERROR);
    $assert($data['memberId'] === 'member-0001' && count($data['accounts']) === 2);
    $assert($data['accounts'][0]['digitalBankingBalance']['minorUnits'] === 245075 && $data['accounts'][0]['availableBalance']['minorUnits'] === 238575);
    $assert($data['accounts'][1]['ledgerBalance']['minorUnits'] === 812000);
    foreach (['Northstar', 'Heritage', 'SOAP', 'REST', 'customerKey', 'productKey', 'HC-'] as $term) $assert(!str_contains($json, $term));
});

$test('financial overview HTTP resource succeeds and reuses safe failure mapping', function () use ($assert): void {
    $response = HttpKernelFactory::create()->dispatch('GET', '/api/members/member-0001/financial-overview');
    $data = json_decode($response->body, true, flags: JSON_THROW_ON_ERROR);
    $assert($response->status === 200 && $response->headers['Content-Type'] === 'application/json; charset=utf-8');
    $assert(count($data['accounts']) === 2 && $data['accounts'][0]['availableBalance']['formatted'] === '$2,385.75' && $data['accounts'][1]['ledgerBalance']['minorUnits'] === 812000);
    $assert(HttpKernelFactory::create('customer-not-found')->dispatch('GET', '/api/members/member-0001/financial-overview')->status === 404);
    $assert(HttpKernelFactory::create()->dispatch('GET', '/api/members/member-9999/financial-overview')->status === 404);
    $assert(HttpKernelFactory::create('vendor-timeout')->dispatch('GET', '/api/members/member-0001/financial-overview')->status === 504);
    $temporary = HttpKernelFactory::create('normal', 'temporary-unavailable')->dispatch('GET', '/api/members/member-0001/financial-overview');
    $malformed = HttpKernelFactory::create('normal', 'malformed-xml')->dispatch('GET', '/api/members/member-0001/financial-overview');
    $assert($temporary->status === 503 && $malformed->status === 502);
    foreach (['Northstar', 'Heritage', 'SOAP', 'HC-'] as $term) $assert(!str_contains($temporary->body . $malformed->body, $term));
});

$test('chapter 10 schema declares tables keys and foreign keys', function () use ($assert): void {
    $pdo=\Harbor\DigitalBankingLab\Infrastructure\Database\LaboratoryDatabase::create(dirname(__DIR__));
    $tables=$pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $assert($tables === ['account_activity','accounts','members']);
    $assert($pdo->query("PRAGMA foreign_key_list(accounts)")->fetch()['table']==='members');
    $assert($pdo->query("PRAGMA foreign_key_list(account_activity)")->fetch()['table']==='accounts');
});

$test('SQL fixtures and mappings are deterministic Harbor values', function () use ($assert): void {
    $repo=new \Harbor\DigitalBankingLab\Infrastructure\Database\SqlMemberActivityRepository(\Harbor\DigitalBankingLab\Infrastructure\Database\LaboratoryDatabase::create(dirname(__DIR__)));
    $members=$repo->members(); $accounts=$repo->accountsFor(new MemberId('member-0001'));
    $assert(array_map(fn($m)=>$m->id->value,$members)===['member-0001','member-0002','member-0003']);
    $assert($members[0]->status===MembershipStatus::ACTIVE && $members[0]->createdAt->format('c')==='2024-01-10T09:00:00+00:00');
    $assert(array_map(fn($a)=>$a->id->value,$accounts)===['account-0001','account-0002']);
    $assert($accounts[0]->type===AccountType::CHECKING && $accounts[0]->status===AccountStatus::OPEN);
    $assert($repo->accountsFor(new MemberId('member-9999'))===[]);
});

$test('recent activity grouping and integer aggregation are correct', function () use ($assert): void {
    $repo=new \Harbor\DigitalBankingLab\Infrastructure\Database\SqlMemberActivityRepository(\Harbor\DigitalBankingLab\Infrastructure\Database\LaboratoryDatabase::create(dirname(__DIR__)));
    $recent=$repo->activitySince(new AccountId('account-0001'),new DateTimeImmutable('2025-07-19T14:30:00Z'));
    $assert(array_map(fn($a)=>$a->id,$recent)===['activity-0002','activity-0003']);
    $latest=$repo->mostRecentActivityByAccount(); $assert($latest['account-0001']->format('c')==='2026-01-05T15:30:00+00:00' && $latest['account-0004']===null);
    $assert($repo->totalActivityAmount(new AccountId('account-0001'))->minorUnits===55785);
});

$test('NOT EXISTS answers inactivity while old-row pitfall does not', function () use ($assert): void {
    $repo=new \Harbor\DigitalBankingLab\Infrastructure\Database\SqlMemberActivityRepository(\Harbor\DigitalBankingLab\Infrastructure\Database\LaboratoryDatabase::create(dirname(__DIR__)));
    $cutoff=new DateTimeImmutable('2025-07-19T14:30:00Z');
    $correct=array_map(fn($m)=>$m->id->value,$repo->inactiveMembers($cutoff));
    $assert($correct===['member-0002','member-0003']);
    $assert($repo->incorrectInactiveMemberIds($cutoff)===['member-0001','member-0002']);
    $changed=array_map(fn($m)=>$m->id->value,$repo->inactiveMembers(new DateTimeImmutable('2026-01-10T00:00:00Z')));
    $assert($changed===['member-0001','member-0002','member-0003']);
});

$test('SQL parameters resist malicious identifiers and stored enums fail clearly', function () use ($assert): void {
    $pdo=\Harbor\DigitalBankingLab\Infrastructure\Database\LaboratoryDatabase::create(dirname(__DIR__)); $repo=new \Harbor\DigitalBankingLab\Infrastructure\Database\SqlMemberActivityRepository($pdo);
    $assert($repo->accountsFor(new MemberId("member-0001' OR 1=1 --"))===[]);
    $assert((int)$pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn()===4);
    $pdo->exec("UPDATE members SET membership_status='CORRUPT' WHERE member_id='member-0001'");
    try{$repo->members();$assert(false);}catch(UnexpectedValueException $e){$assert(str_contains($e->getMessage(),'CORRUPT'));}
});

$test('inactive member service exposes a Harbor port and Harbor results', function () use ($assert): void {
    $pdo=\Harbor\DigitalBankingLab\Infrastructure\Database\LaboratoryDatabase::create(dirname(__DIR__)); $repo=new \Harbor\DigitalBankingLab\Infrastructure\Database\SqlMemberActivityRepository($pdo);
    $service=new \Harbor\DigitalBankingLab\Application\FindInactiveMembers($repo); $result=$service->execute(new DateTimeImmutable('2025-07-19T14:30:00Z'));
    $assert($repo instanceof \Harbor\DigitalBankingLab\Application\MemberActivityRepository && $result[0] instanceof \Harbor\DigitalBankingLab\Application\InactiveMemberSummary);
    $reflection=new ReflectionClass($service); $assert(!$reflection->hasProperty('pdo'));
});

$test('chapter 11 factual projection preserves totals recent facts and never-active members', function () use ($assert): void {
    $repo=new \Harbor\DigitalBankingLab\Infrastructure\Database\SqlMemberActivityRepository(\Harbor\DigitalBankingLab\Infrastructure\Database\LaboratoryDatabase::create(dirname(__DIR__)));
    $facts=$repo->activityFacts(new DateTimeImmutable('2025-07-19T14:30:00Z'));
    $assert(array_map(fn($f)=>$f->memberId->value,$facts)===['member-0001','member-0002','member-0003']);
    $assert([$facts[0]->accountCount,$facts[0]->totalActivityCount,$facts[0]->recentActivityCount,$facts[0]->mostRecentActivityAt->format('c')] === [2,3,2,'2026-01-05T15:30:00+00:00']);
    $assert([$facts[2]->accountCount,$facts[2]->totalActivityCount,$facts[2]->recentActivityCount,$facts[2]->mostRecentActivityAt] === [1,0,0,null]);
});

$test('activity policy cutoff and inclusive SQL boundary are deterministic', function () use ($assert): void {
    $policy=\Harbor\DigitalBankingLab\Application\ActivityPolicy::inactiveAfterDays(180); $asOf=new DateTimeImmutable('2026-01-15T14:30:00Z');
    $assert($policy->inactiveAfterDays===180 && $policy->cutoff($asOf)->format('c')==='2025-07-19T14:30:00+00:00');
    $assert(\Harbor\DigitalBankingLab\Application\ActivityPolicy::inactiveAfterDays(90)->cutoff($asOf)->format('c')==='2025-10-17T14:30:00+00:00');
    $pdo=\Harbor\DigitalBankingLab\Infrastructure\Database\LaboratoryDatabase::create(dirname(__DIR__)); $pdo->exec("INSERT INTO account_activity VALUES ('boundary','account-0003','DEPOSIT','2025-07-19T14:30:00Z',1)");
    $facts=(new \Harbor\DigitalBankingLab\Infrastructure\Database\SqlMemberActivityRepository($pdo))->activityFactsFor(new MemberId('member-0002'),$policy->cutoff($asOf));
    $assert($facts->recentActivityCount===1,'occurred_at >= cutoff must be inclusive');
});

$test('classification and derived elapsed days are explicit application decisions', function () use ($assert): void {
    $repo=new \Harbor\DigitalBankingLab\Infrastructure\Database\SqlMemberActivityRepository(\Harbor\DigitalBankingLab\Infrastructure\Database\LaboratoryDatabase::create(dirname(__DIR__))); $policy=\Harbor\DigitalBankingLab\Application\ActivityPolicy::laboratoryDefault(); $asOf=new DateTimeImmutable('2026-01-15T14:30:00Z');
    $service=new \Harbor\DigitalBankingLab\Application\GetMemberActivityProfile($repo,$policy);
    $one=$service->execute(new MemberId('member-0001'),$asOf); $two=$service->execute(new MemberId('member-0002'),$asOf); $three=$service->execute(new MemberId('member-0003'),$asOf);
    $assert([$one->classification->value,$two->classification->value,$three->classification->value]===['RECENTLY_ACTIVE','INACTIVE','NEVER_ACTIVE']);
    $assert($one->daysSinceLastActivity===9 && $two->daysSinceLastActivity===228 && $three->daysSinceLastActivity===null);
    $reflection=new ReflectionClass($service); $assert(!$reflection->hasProperty('pdo'));
});

$test('activity review is explainable excludes recent and has deterministic order', function () use ($assert): void {
    $repo=new \Harbor\DigitalBankingLab\Infrastructure\Database\SqlMemberActivityRepository(\Harbor\DigitalBankingLab\Infrastructure\Database\LaboratoryDatabase::create(dirname(__DIR__)));
    $result=(new \Harbor\DigitalBankingLab\Application\FindMembersForActivityReview($repo,\Harbor\DigitalBankingLab\Application\ActivityPolicy::laboratoryDefault()))->execute(new DateTimeImmutable('2026-01-15T14:30:00Z'));
    $assert(array_map(fn($c)=>$c->memberId->value,$result)===['member-0002','member-0003']);
    $assert($result[0]->reason==='No activity within the 180-day laboratory window' && str_contains($result[1]->reason,'No activity records'));
});

$test('activity profile API is intentional and preserves existing routes', function () use ($assert): void {
    $response=HttpKernelFactory::create()->dispatch('GET','/api/members/member-0001/activity-profile'); $data=json_decode($response->body,true,flags:JSON_THROW_ON_ERROR);
    $assert($response->status===200 && $data['policy']['inactiveAfterDays']===180 && $data['classification']==='recently_active');
    $assert($data['facts']===['accountCount'=>2,'activityCount'=>3,'recentActivityCount'=>2,'mostRecentActivityAt'=>'2026-01-05T15:30:00Z','daysSinceLastActivity'=>9]);
    $assert(!str_contains($response->body,'member_id') && HttpKernelFactory::create()->dispatch('GET','/api/members/member-9999/activity-profile')->status===404);
    $assert(HttpKernelFactory::create()->dispatch('GET','/api/members/member-0001')->status===200);
});

$failures = 0;
foreach ($tests as $name => $body) {
    try { $body(); echo "PASS {$name}\n"; }
    catch (Throwable $error) { ++$failures; fwrite(STDERR, "FAIL {$name}: {$error->getMessage()}\n"); }
}
echo sprintf("\n%d tests, %d failures\n", count($tests), $failures);
exit($failures === 0 ? 0 : 1);
