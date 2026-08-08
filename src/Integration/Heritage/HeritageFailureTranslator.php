<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Heritage;

use Harbor\DigitalBankingLab\Application\{IntegrationFailure, IntegrationFailureCategory as Category, IntegrationOperation, RetryDisposition as Retry};
use Harbor\DigitalBankingLab\Integration\Heritage\Exception\{HeritageAccountNotFound, HeritageCoreError, HeritageResponseDecodingFailure, HeritageTransportFailure};
use Harbor\DigitalBankingLab\Integration\VendorTranslationException;

final readonly class HeritageFailureTranslator
{
    public function translate(\Throwable $error): IntegrationFailure
    {
        [$category, $retry, $code] = match (true) {
            $error instanceof HeritageAccountNotFound => [Category::NOT_FOUND, Retry::NOT_RETRYABLE, 'HERITAGE_SOAP_ACCOUNT_NOT_FOUND'],
            $error instanceof HeritageCoreError => [Category::EXTERNAL_ERROR, Retry::UNKNOWN, 'HERITAGE_SOAP_CORE_ERROR'],
            $error instanceof HeritageTransportFailure => [Category::TEMPORARY_UNAVAILABLE, Retry::RETRYABLE, 'HERITAGE_TRANSPORT_FAILURE'],
            $error instanceof HeritageResponseDecodingFailure => [Category::INVALID_EXTERNAL_RESPONSE, Retry::NOT_RETRYABLE, str_contains($error->getMessage(), 'malformed') ? 'HERITAGE_INVALID_XML' : 'HERITAGE_INCOMPLETE_RESPONSE'],
            $error instanceof VendorTranslationException => [Category::UNSUPPORTED_EXTERNAL_VALUE, Retry::NOT_RETRYABLE, str_contains($error->getMessage(), 'Currency') ? 'HERITAGE_UNSUPPORTED_CURRENCY' : 'HERITAGE_UNSUPPORTED_STATUS'],
            default => throw new \InvalidArgumentException('Not a recognized Heritage boundary failure.'),
        };
        return new IntegrationFailure($category, $retry, 'A required external service failed.', 'Heritage Core Banking', IntegrationOperation::ACCOUNT_BALANCE_LOOKUP, $code, $error);
    }
}
