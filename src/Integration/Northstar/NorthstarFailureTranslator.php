<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Integration\Northstar;

use Harbor\DigitalBankingLab\Application\{IntegrationFailure, IntegrationFailureCategory as Category, IntegrationOperation, RetryDisposition as Retry};
use Harbor\DigitalBankingLab\Integration\Northstar\Exception\{NorthstarCustomerNotFound, NorthstarHttpFailure, NorthstarResponseDecodingFailure, NorthstarTimeoutFailure, NorthstarUnavailableFailure};
use Harbor\DigitalBankingLab\Integration\VendorTranslationException;

final readonly class NorthstarFailureTranslator
{
    public function translate(\Throwable $error): IntegrationFailure
    {
        [$category, $retry, $code] = match (true) {
            $error instanceof NorthstarTimeoutFailure => [Category::TIMEOUT, Retry::RETRYABLE, 'NORTHSTAR_TIMEOUT'],
            $error instanceof NorthstarUnavailableFailure => [Category::TEMPORARY_UNAVAILABLE, Retry::RETRYABLE, 'NORTHSTAR_UNAVAILABLE'],
            $error instanceof NorthstarCustomerNotFound => [Category::NOT_FOUND, Retry::NOT_RETRYABLE, 'NORTHSTAR_HTTP_404'],
            $error instanceof NorthstarHttpFailure => [Category::EXTERNAL_ERROR, Retry::UNKNOWN, 'NORTHSTAR_HTTP_' . $error->statusCode],
            $error instanceof NorthstarResponseDecodingFailure => [Category::INVALID_EXTERNAL_RESPONSE, Retry::NOT_RETRYABLE, str_contains($error->getMessage(), 'malformed') ? 'NORTHSTAR_INVALID_JSON' : 'NORTHSTAR_INCOMPLETE_RESPONSE'],
            $error instanceof VendorTranslationException => [Category::UNSUPPORTED_EXTERNAL_VALUE, Retry::NOT_RETRYABLE, 'NORTHSTAR_UNSUPPORTED_PRODUCT'],
            default => throw new \InvalidArgumentException('Not a recognized Northstar boundary failure.'),
        };
        return new IntegrationFailure($category, $retry, 'A required external service failed.', 'Northstar Digital Banking', IntegrationOperation::MEMBER_LOOKUP, $code, $error);
    }
}
