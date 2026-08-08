<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

use Harbor\DigitalBankingLab\Api\IntegrationFailureApiMapper;

final readonly class IntegrationFailureReport
{
    public function __construct(private IntegrationFailureApiMapper $api = new IntegrationFailureApiMapper()) {}

    public function render(string $scenario, IntegrationFailure $failure): string
    {
        $mapping = $this->api->map($failure);
        return "Scenario: {$scenario}\n"
            . "Operation: {$failure->operation->value}\n"
            . "External system: {$failure->externalSystem}\n"
            . "Harbor failure category: {$failure->category->value}\n"
            . "Retry disposition: {$failure->retryDisposition->value}\n"
            . "Diagnostic code: {$failure->diagnosticCode}\n"
            . "Public API mapping: {$mapping['status']} {$mapping['error']->code}\n";
    }
}
