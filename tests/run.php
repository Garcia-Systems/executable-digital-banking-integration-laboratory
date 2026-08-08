<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Harbor\DigitalBankingLab\Application\EcosystemRenderer;
use Harbor\DigitalBankingLab\Domain\SystemOwnership;
use Harbor\DigitalBankingLab\Fixtures\HarborEcosystemFixture;

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

$failures = 0;
foreach ($tests as $name => $body) {
    try { $body(); echo "PASS {$name}\n"; }
    catch (Throwable $error) { ++$failures; fwrite(STDERR, "FAIL {$name}: {$error->getMessage()}\n"); }
}
echo sprintf("\n%d tests, %d failures\n", count($tests), $failures);
exit($failures === 0 ? 0 : 1);
