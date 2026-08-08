<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Http;

use Harbor\DigitalBankingLab\Api\MemberSummaryPresenter;
use Harbor\DigitalBankingLab\Application\GetMemberSummary;
use Harbor\DigitalBankingLab\Integration\VendorIdentityMap;
use Harbor\DigitalBankingLab\Integration\Northstar\{DeterministicNorthstarClient, NorthstarDigitalBankingAdapter, NorthstarTranslator};

final class HttpKernelFactory
{
    public static function create(): Router
    {
        $gateway = new NorthstarDigitalBankingAdapter(new DeterministicNorthstarClient(), VendorIdentityMap::laboratory(), new NorthstarTranslator());
        return new Router(new MemberController(new GetMemberSummary($gateway), new MemberSummaryPresenter()));
    }
}
