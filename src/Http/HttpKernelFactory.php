<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Http;

use Harbor\DigitalBankingLab\Api\MemberSummaryPresenter;
use Harbor\DigitalBankingLab\Composition\LaboratoryApplicationFactory;

final class HttpKernelFactory
{
    public static function create(): Router
    {
        $applications = new LaboratoryApplicationFactory(dirname(__DIR__, 2));
        return new Router(new MemberController($applications->getMemberSummary(), new MemberSummaryPresenter()));
    }
}
