<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Composition;

use Harbor\DigitalBankingLab\Application\{AccountBalanceGateway, DigitalBankingGateway, GetAccountBalanceDetails, GetMemberSummary};

use Harbor\DigitalBankingLab\Infrastructure\Http\DeterministicHttpClient;
use Harbor\DigitalBankingLab\Infrastructure\Soap\DeterministicHeritageSoapTransport;
use Harbor\DigitalBankingLab\Integration\VendorIdentityMap;
use Harbor\DigitalBankingLab\Integration\Heritage\{HeritageCoreBankingAdapter, HeritageIdentityMap, HeritageSoapClient, HeritageSoapEnvelopeBuilder};
use Harbor\DigitalBankingLab\Integration\Northstar\{DeterministicNorthstarClient, NorthstarDigitalBankingAdapter, NorthstarRestClient, NorthstarTranslator};

/** The composition root: this outer layer alone chooses concrete integration adapters. */
final readonly class LaboratoryApplicationFactory
{
    public function __construct(private string $projectRoot) {}

    public function digitalBankingGateway(bool $restBacked = true, string $scenario = 'normal'): DigitalBankingGateway
    {
        $client = $restBacked
            ? new NorthstarRestClient(new DeterministicHttpClient($scenario, $this->projectRoot . '/fixtures/northstar'), 'https://northstar.invalid')
            : new DeterministicNorthstarClient($scenario);
        return new NorthstarDigitalBankingAdapter($client, VendorIdentityMap::laboratory(), new NorthstarTranslator());
    }

    public function accountBalanceGateway(string $scenario = 'normal'): AccountBalanceGateway
    {
        $client = new HeritageSoapClient(new DeterministicHeritageSoapTransport($scenario), new HeritageSoapEnvelopeBuilder(), 'https://heritage-core.invalid/soap');
        return new HeritageCoreBankingAdapter($client, HeritageIdentityMap::laboratory());
    }

    public function getMemberSummary(bool $restBacked = true): GetMemberSummary
    {
        return new GetMemberSummary($this->digitalBankingGateway($restBacked));
    }

    public function getAccountBalanceDetails(): GetAccountBalanceDetails
    {
        return new GetAccountBalanceDetails($this->accountBalanceGateway());
    }
}
