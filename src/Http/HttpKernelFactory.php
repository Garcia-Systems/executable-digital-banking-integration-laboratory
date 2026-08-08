<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Http;

use Harbor\DigitalBankingLab\Api\MemberSummaryPresenter;
use Harbor\DigitalBankingLab\Application\GetMemberSummary;
use Harbor\DigitalBankingLab\Infrastructure\Http\DeterministicHttpClient;
use Harbor\DigitalBankingLab\Integration\VendorIdentityMap;
use Harbor\DigitalBankingLab\Integration\Northstar\{NorthstarDigitalBankingAdapter, NorthstarRestClient, NorthstarTranslator};

final class HttpKernelFactory
{
    public static function create(): Router
    {
        $fixtures = dirname(__DIR__, 2) . '/fixtures/northstar';
        $client = new NorthstarRestClient(new DeterministicHttpClient('normal', $fixtures), 'https://northstar.invalid');
        $gateway = new NorthstarDigitalBankingAdapter($client, VendorIdentityMap::laboratory(), new NorthstarTranslator());
        return new Router(new MemberController(new GetMemberSummary($gateway), new MemberSummaryPresenter()));
    }
}
