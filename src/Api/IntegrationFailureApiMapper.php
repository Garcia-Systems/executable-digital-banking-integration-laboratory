<?php
declare(strict_types=1);
namespace Harbor\DigitalBankingLab\Api;

use Harbor\DigitalBankingLab\Application\{IntegrationFailure, IntegrationFailureCategory, IntegrationOperation};

final readonly class IntegrationFailureApiMapper
{
    /** @return array{status:int,error:ApiError} */
    public function map(IntegrationFailure $failure): array
    {
        if ($failure->category === IntegrationFailureCategory::NOT_FOUND) {
            return $failure->operation === IntegrationOperation::MEMBER_LOOKUP
                ? ['status' => 404, 'error' => new ApiError('member_not_found', 'Member was not found.')]
                : ['status' => 404, 'error' => new ApiError('account_not_found', 'Account was not found.')];
        }
        return match ($failure->category) {
            IntegrationFailureCategory::TIMEOUT => ['status' => 504, 'error' => new ApiError('upstream_timeout', 'A required service did not respond in time.')],
            IntegrationFailureCategory::TEMPORARY_UNAVAILABLE, IntegrationFailureCategory::EXTERNAL_ERROR => ['status' => 503, 'error' => new ApiError('service_temporarily_unavailable', 'This service is temporarily unavailable. Please try again later.')],
            IntegrationFailureCategory::INVALID_EXTERNAL_RESPONSE, IntegrationFailureCategory::UNSUPPORTED_EXTERNAL_VALUE => ['status' => 502, 'error' => new ApiError('upstream_invalid_response', 'A required service returned an invalid response.')],
            default => throw new \LogicException('Unmapped integration failure category.'),
        };
    }
}
