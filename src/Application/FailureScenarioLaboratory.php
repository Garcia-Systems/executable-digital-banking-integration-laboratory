<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Composition\LaboratoryApplicationFactory;
use Harbor\DigitalBankingLab\Domain\Member\{AccountId, MemberId};

final readonly class FailureScenarioLaboratory
{
    /** @var array<string, array{string,string}> */
    public const SCENARIOS = [
        'northstar-timeout' => ['northstar', 'vendor-timeout'],
        'northstar-unavailable' => ['northstar', 'vendor-unavailable'],
        'northstar-not-found' => ['northstar', 'customer-not-found'],
        'northstar-http-error' => ['northstar', 'vendor-error'],
        'northstar-malformed-json' => ['northstar', 'malformed-json'],
        'northstar-incomplete-response' => ['northstar', 'incomplete-response'],
        'northstar-unsupported-product' => ['northstar', 'unsupported-product'],
        'heritage-account-not-found' => ['heritage', 'account-not-found'],
        'heritage-core-error' => ['heritage', 'core-error'],
        'heritage-malformed-xml' => ['heritage', 'malformed-xml'],
        'heritage-incomplete-response' => ['heritage', 'incomplete-response'],
        'heritage-unsupported-status' => ['heritage', 'unsupported-status'],
        'heritage-unsupported-currency' => ['heritage', 'unsupported-currency'],
    ];

    public function __construct(private LaboratoryApplicationFactory $applications) {}

    public function run(string $scenario): IntegrationFailure
    {
        [$system, $fixture] = self::SCENARIOS[$scenario] ?? throw new \InvalidArgumentException("Unknown failure scenario: {$scenario}");
        try {
            if ($system === 'northstar') $this->applications->getMemberSummary(true, $fixture)->execute(new MemberId('member-0001'));
            else (new GetAccountBalanceDetails($this->applications->accountBalanceGateway($fixture)))->execute(new AccountId('account-0001'));
        } catch (IntegrationFailure $failure) { return $failure; }
        throw new \LogicException("Failure scenario unexpectedly succeeded: {$scenario}");
    }
}
