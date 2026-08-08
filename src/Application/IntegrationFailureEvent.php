<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

/** A deliberately whitelisted operational record: no arbitrary context bag exists. */
final readonly class IntegrationFailureEvent
{
    public function __construct(
        public string $externalSystem,
        public IntegrationOperation $operation,
        public IntegrationFailureCategory $category,
        public RetryDisposition $retryDisposition,
        public string $diagnosticCode,
    ) {}

    public static function fromFailure(IntegrationFailure $failure): self
    {
        return new self($failure->externalSystem, $failure->operation, $failure->category, $failure->retryDisposition, $failure->diagnosticCode);
    }

    public function render(): string
    {
        return "External system: {$this->externalSystem}\nOperation: {$this->operation->value}\nCategory: {$this->category->value}\nRetry: {$this->retryDisposition->value}\nDiagnostic code: {$this->diagnosticCode}\n";
    }
}
