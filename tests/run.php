<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Harbor\DigitalBankingLab\Application\EcosystemRenderer;
use Harbor\DigitalBankingLab\Application\LaboratoryRenderer;
use Harbor\DigitalBankingLab\Domain\FixedClock;
use Harbor\DigitalBankingLab\Domain\SequenceIdGenerator;
use Harbor\DigitalBankingLab\Domain\SystemOwnership;
use Harbor\DigitalBankingLab\Domain\VendorStatus;
use Harbor\DigitalBankingLab\Fixtures\HarborEcosystemFixture;
use Harbor\DigitalBankingLab\Fixtures\LaboratoryFixtureFactory;

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

$failures = 0;
foreach ($tests as $name => $body) {
    try { $body(); echo "PASS {$name}\n"; }
    catch (Throwable $error) { ++$failures; fwrite(STDERR, "FAIL {$name}: {$error->getMessage()}\n"); }
}
echo sprintf("\n%d tests, %d failures\n", count($tests), $failures);
exit($failures === 0 ? 0 : 1);
