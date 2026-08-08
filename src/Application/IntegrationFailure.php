<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Application;

/** Harbor-owned failure crossing an integration boundary. It intentionally contains no payload or member data. */
final class IntegrationFailure extends \RuntimeException
{
    public function __construct(
        public readonly IntegrationFailureCategory $category,
        public readonly RetryDisposition $retryDisposition,
        public readonly string $safeSummary,
        public readonly string $externalSystem,
        public readonly IntegrationOperation $operation,
        public readonly string $diagnosticCode,
        ?\Throwable $previous = null,
    ) { parent::__construct($safeSummary, previous: $previous); }
}
