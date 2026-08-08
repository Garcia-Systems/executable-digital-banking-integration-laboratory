<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Composition;

use Harbor\DigitalBankingLab\Http\Router;
use Harbor\DigitalBankingLab\Http\HttpKernelFactory;
use Harbor\DigitalBankingLab\Infrastructure\Database\LaboratoryDatabase;
use Harbor\DigitalBankingLab\Infrastructure\Http\DeterministicHttpClient;
use Harbor\DigitalBankingLab\Infrastructure\Soap\DeterministicHeritageSoapTransport;
use Harbor\DigitalBankingLab\Integration\ClearVerify\DeterministicClearVerifyHttpClient;

/**
 * The deterministic integration-test composition root.
 *
 * Every database call creates and seeds a new in-memory SQLite connection. Vendor
 * transports record requests and can only read local fixtures; production
 * configuration and credentials never enter this composition.
 */
final readonly class IntegrationTestApplicationFactory
{
    public function __construct(private string $projectRoot) {}

    public function applications(): LaboratoryApplicationFactory
    {
        return new LaboratoryApplicationFactory($this->projectRoot);
    }

    public function database(): \PDO
    {
        return LaboratoryDatabase::create($this->projectRoot);
    }

    public function router(string $northstar = 'normal', string $heritage = 'normal', string $clearVerify = 'verification-pass'): Router
    {
        return HttpKernelFactory::create($northstar, $heritage, $clearVerify);
    }

    public function northstarTransport(string $scenario = 'normal'): DeterministicHttpClient
    {
        return new DeterministicHttpClient($scenario, $this->projectRoot . '/fixtures/northstar');
    }

    public function heritageTransport(string $scenario = 'normal'): DeterministicHeritageSoapTransport
    {
        return new DeterministicHeritageSoapTransport($scenario);
    }

    public function clearVerifyTransport(string $scenario = 'verification-pass'): DeterministicClearVerifyHttpClient
    {
        return new DeterministicClearVerifyHttpClient($scenario, $this->projectRoot . '/fixtures/clearverify');
    }
}
