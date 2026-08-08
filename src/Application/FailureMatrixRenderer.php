<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Api\IntegrationFailureApiMapper;

final readonly class FailureMatrixRenderer
{
    public function __construct(private IntegrationFailureApiMapper $api = new IntegrationFailureApiMapper()) {}
    public function render(FailureScenarioLaboratory $laboratory): string
    {
        $output = "Scenario | Category | Retry | API Status | API Code\n";
        $output .= "--- | --- | --- | --- | ---\n";
        foreach (array_keys(FailureScenarioLaboratory::SCENARIOS) as $scenario) {
            $failure = $laboratory->run($scenario); $mapping = $this->api->map($failure);
            $output .= "{$scenario} | {$failure->category->value} | {$failure->retryDisposition->value} | {$mapping['status']} | {$mapping['error']->code}\n";
        }
        return $output;
    }
}
