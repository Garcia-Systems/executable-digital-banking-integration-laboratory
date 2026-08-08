<?php

declare(strict_types=1);

namespace Harbor\DigitalBankingLab\Http;

use Harbor\DigitalBankingLab\Api\{MemberFinancialOverviewPresenter, MemberSummaryPresenter};
use Harbor\DigitalBankingLab\Composition\LaboratoryApplicationFactory;

final class HttpKernelFactory
{
    public static function create(string $northstarScenario = 'normal', string $heritageScenario = 'normal'): Router
    {
        $applications = new LaboratoryApplicationFactory(dirname(__DIR__, 2));
        return new Router(
            new MemberController($applications->getMemberSummary(true, $northstarScenario), new MemberSummaryPresenter()),
            new MemberFinancialOverviewController($applications->getMemberFinancialOverview($northstarScenario, $heritageScenario), new MemberFinancialOverviewPresenter()),
        );
    }
}
